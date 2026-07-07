/**
 * pulseEngine.js — PULSE: Social Intelligence Engine
 *
 * User engagement, behavior tracking, cohort analysis, churn prediction,
 * satisfaction scoring, community analytics, collaboration patterns,
 * influence mapping, and feedback loop management.
 *
 * Intelligence Type: Social / Interpersonal
 * Tools: 9
 */

const engagementStore = new Map(); // userId → [{action, ts}]
const cohortStore = new Map();     // cohortId → {users, metrics}
const feedbackStore = new Map();   // channel → [{feedback, ts}]

/**
 * Measure user engagement metrics
 */
export async function engagement(userId, action = 'report', data = {}) {
  if (!engagementStore.has(userId)) engagementStore.set(userId, []);
  const history = engagementStore.get(userId);

  if (action === 'track') {
    history.push({
      action: data.action || 'visit',
      feature: data.feature || 'general',
      timestamp: new Date().toISOString(),
      duration: data.duration || 0,
      value: data.value || 1
    });
    return { tracked: true, userId, total_events: history.length };
  }

  if (action === 'report') {
    if (history.length === 0) return { userId, message: 'No engagement data. Use action "track" first.' };
    
    const now = Date.now();
    const day = 86400000;
    const last7 = history.filter(e => now - new Date(e.timestamp).getTime() < 7 * day).length;
    const last30 = history.filter(e => now - new Date(e.timestamp).getTime() < 30 * day).length;
    const features = {};
    history.forEach(e => { features[e.feature] = (features[e.feature] || 0) + 1; });
    const totalDuration = history.reduce((s, e) => s + (e.duration || 0), 0);
    const avgSessionLen = totalDuration / history.length;

    let level = 'inactive';
    if (last7 >= 20) level = 'power_user';
    else if (last7 >= 10) level = 'active';
    else if (last7 >= 3) level = 'casual';
    else if (last30 >= 1) level = 'dormant';

    return {
      userId,
      engagement_level: level,
      total_events: history.length,
      last_7_days: last7,
      last_30_days: last30,
      top_features: Object.entries(features).sort((a, b) => b[1] - a[1]).slice(0, 5),
      avg_session_seconds: Math.round(avgSessionLen),
      total_time_seconds: totalDuration,
      last_active: history[history.length - 1]?.timestamp,
      health_score: Math.min(100, Math.round(last7 * 5 + last30 * 0.5 + (avgSessionLen > 60 ? 20 : 0)))
    };
  }

  return { hint: 'Actions: track {action, feature, duration}, report' };
}

/**
 * Track user behavior patterns
 */
export async function behaviorTrack(userId, events) {
  // events: [{action, page, timestamp, metadata?}]
  const sessions = [];
  let currentSession = [];
  const sessionGap = 30 * 60 * 1000; // 30 min gap = new session

  const sorted = events.sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp));
  
  sorted.forEach((event, i) => {
    if (i === 0 || new Date(event.timestamp) - new Date(sorted[i - 1].timestamp) > sessionGap) {
      if (currentSession.length) sessions.push([...currentSession]);
      currentSession = [event];
    } else {
      currentSession.push(event);
    }
  });
  if (currentSession.length) sessions.push(currentSession);

  const pages = {};
  const actions = {};
  events.forEach(e => {
    if (e.page) pages[e.page] = (pages[e.page] || 0) + 1;
    if (e.action) actions[e.action] = (actions[e.action] || 0) + 1;
  });

  return {
    userId,
    total_events: events.length,
    sessions: sessions.length,
    avg_session_length: Math.round(sessions.reduce((s, sess) => {
      if (sess.length < 2) return s;
      return s + (new Date(sess[sess.length - 1].timestamp) - new Date(sess[0].timestamp)) / 1000;
    }, 0) / sessions.length),
    top_pages: Object.entries(pages).sort((a, b) => b[1] - a[1]).slice(0, 5),
    action_breakdown: actions,
    patterns: {
      most_common_entry: sessions.map(s => s[0]?.page).filter(Boolean).reduce((acc, p) => { acc[p] = (acc[p] || 0) + 1; return acc; }, {}),
      most_common_exit: sessions.map(s => s[s.length - 1]?.page).filter(Boolean).reduce((acc, p) => { acc[p] = (acc[p] || 0) + 1; return acc; }, {}),
      bounce_rate: Math.round(sessions.filter(s => s.length === 1).length / sessions.length * 100)
    }
  };
}

/**
 * Analyze user cohorts
 */
export async function cohortAnalyze(cohortId, users, metrics = {}) {
  cohortStore.set(cohortId, { users, metrics, created: new Date().toISOString() });

  const values = users.map(u => u.value || u.score || 0);
  const avg = values.length ? values.reduce((a, b) => a + b, 0) / values.length : 0;
  const segments = {
    high: users.filter(u => (u.value || u.score || 0) > avg * 1.5),
    medium: users.filter(u => { const v = u.value || u.score || 0; return v >= avg * 0.5 && v <= avg * 1.5; }),
    low: users.filter(u => (u.value || u.score || 0) < avg * 0.5)
  };

  return {
    cohort_id: cohortId,
    size: users.length,
    average_value: Math.round(avg * 100) / 100,
    segments: {
      high_value: { count: segments.high.length, pct: Math.round(segments.high.length / users.length * 100) },
      medium_value: { count: segments.medium.length, pct: Math.round(segments.medium.length / users.length * 100) },
      low_value: { count: segments.low.length, pct: Math.round(segments.low.length / users.length * 100) }
    },
    retention: metrics.retention || null,
    top_users: users.sort((a, b) => (b.value || b.score || 0) - (a.value || a.score || 0)).slice(0, 5).map(u => ({ id: u.id || u.name, value: u.value || u.score }))
  };
}

/**
 * Predict user churn risk
 */
export async function churnPredict(userId, userMetrics) {
  // userMetrics: {last_login_days_ago, sessions_last_30, avg_session_min, support_tickets, plan_age_months, feature_adoption_pct}
  let riskScore = 0;
  const factors = [];

  const m = {
    last_login_days_ago: userMetrics.last_login_days_ago ?? 0,
    sessions_last_30: userMetrics.sessions_last_30 ?? 10,
    avg_session_min: userMetrics.avg_session_min ?? 5,
    support_tickets: userMetrics.support_tickets ?? 0,
    plan_age_months: userMetrics.plan_age_months ?? 1,
    feature_adoption_pct: userMetrics.feature_adoption_pct ?? 50
  };

  if (m.last_login_days_ago > 14) { riskScore += 25; factors.push('Inactive >14 days'); }
  else if (m.last_login_days_ago > 7) { riskScore += 15; factors.push('Inactive >7 days'); }
  if (m.sessions_last_30 < 3) { riskScore += 20; factors.push('Very low session count'); }
  else if (m.sessions_last_30 < 8) { riskScore += 10; factors.push('Below-average sessions'); }
  if (m.avg_session_min < 2) { riskScore += 15; factors.push('Very short sessions'); }
  if (m.support_tickets > 3) { riskScore += 15; factors.push('Multiple support tickets'); }
  if (m.feature_adoption_pct < 20) { riskScore += 20; factors.push('Low feature adoption'); }
  else if (m.feature_adoption_pct < 40) { riskScore += 10; factors.push('Below-average feature adoption'); }
  if (m.plan_age_months <= 2) { riskScore += 10; factors.push('New subscriber — early churn window'); }

  riskScore = Math.min(100, riskScore);
  const risk = riskScore >= 70 ? 'critical' : riskScore >= 40 ? 'high' : riskScore >= 20 ? 'medium' : 'low';

  return {
    userId,
    churn_risk_score: riskScore,
    risk_level: risk,
    risk_factors: factors,
    metrics: m,
    recommended_actions: [
      risk === 'critical' && 'Immediate personal outreach — offer incentive to stay',
      risk === 'critical' && 'Schedule success call within 24 hours',
      risk === 'high' && 'Send re-engagement campaign with feature highlights',
      factors.includes('Low feature adoption') && 'Send onboarding tutorial series',
      factors.includes('Multiple support tickets') && 'Escalate to customer success — review tickets',
      factors.includes('Very short sessions') && 'Offer guided walkthrough or demo',
      risk === 'low' && 'Continue current engagement — user is healthy'
    ].filter(Boolean),
    intervention_window: risk === 'critical' ? '24-48 hours' : risk === 'high' ? '1 week' : '2-4 weeks'
  };
}

/**
 * Measure user satisfaction (NPS/CSAT)
 */
export async function satisfaction(responses, type = 'nps') {
  if (type === 'nps') {
    // NPS: 0-10 scale. Promoters=9-10, Passives=7-8, Detractors=0-6
    const scores = responses.map(r => typeof r === 'object' ? r.score : r);
    const promoters = scores.filter(s => s >= 9).length;
    const passives = scores.filter(s => s >= 7 && s <= 8).length;
    const detractors = scores.filter(s => s <= 6).length;
    const nps = Math.round((promoters - detractors) / scores.length * 100);

    return {
      type: 'NPS',
      total_responses: scores.length,
      nps_score: nps,
      breakdown: {
        promoters: { count: promoters, pct: Math.round(promoters / scores.length * 100) },
        passives: { count: passives, pct: Math.round(passives / scores.length * 100) },
        detractors: { count: detractors, pct: Math.round(detractors / scores.length * 100) }
      },
      benchmark: nps >= 70 ? 'World class' : nps >= 50 ? 'Excellent' : nps >= 30 ? 'Good' : nps >= 0 ? 'Needs improvement' : 'Critical — focus on detractors',
      average_score: Math.round(scores.reduce((a, b) => a + b, 0) / scores.length * 10) / 10
    };
  }

  // CSAT: 1-5 scale
  const scores = responses.map(r => typeof r === 'object' ? r.score : r);
  const satisfied = scores.filter(s => s >= 4).length;
  return {
    type: 'CSAT',
    total_responses: scores.length,
    csat_score: Math.round(satisfied / scores.length * 100),
    average: Math.round(scores.reduce((a, b) => a + b, 0) / scores.length * 100) / 100,
    distribution: [1, 2, 3, 4, 5].map(v => ({ rating: v, count: scores.filter(s => s === v).length }))
  };
}

/**
 * Analyze community/forum activity
 */
export async function community(activities) {
  // activities: [{user, action (post/reply/like/share), timestamp, content?}]
  const users = {};
  const actions = {};
  const hourly = Array(24).fill(0);

  activities.forEach(a => {
    users[a.user] = (users[a.user] || 0) + 1;
    actions[a.action] = (actions[a.action] || 0) + 1;
    if (a.timestamp) hourly[new Date(a.timestamp).getHours()]++;
  });

  const uniqueUsers = Object.keys(users).length;
  const topContributors = Object.entries(users).sort((a, b) => b[1] - a[1]).slice(0, 10);
  const lurkerRatio = activities.length > 0 ? Math.round((1 - uniqueUsers / activities.length) * 100) : 0;

  return {
    total_activities: activities.length,
    unique_users: uniqueUsers,
    action_breakdown: actions,
    engagement_ratio: Math.round(activities.length / uniqueUsers * 10) / 10,
    top_contributors: topContributors.map(([user, count]) => ({ user, contributions: count })),
    peak_hour: hourly.indexOf(Math.max(...hourly)),
    health_indicators: {
      reply_ratio: actions.reply && actions.post ? Math.round(actions.reply / actions.post * 100) / 100 : 0,
      content_creators_pct: Math.round(topContributors.length / uniqueUsers * 100),
      lurker_ratio: lurkerRatio
    },
    recommendations: [
      lurkerRatio > 80 && 'High lurker ratio — add gamification or incentives to participate',
      (!actions.reply || actions.reply < actions.post) && 'Low reply rate — encourage discussion with prompts',
      uniqueUsers < 10 && 'Small community — focus on inviting and onboarding new members'
    ].filter(Boolean)
  };
}

/**
 * Track team collaboration patterns
 */
export async function collaboration(teamId, interactions) {
  // interactions: [{from, to, type (message/review/merge/meeting), timestamp}]
  const memberActivity = {};
  const pairs = {};
  const types = {};

  interactions.forEach(i => {
    memberActivity[i.from] = (memberActivity[i.from] || 0) + 1;
    memberActivity[i.to] = (memberActivity[i.to] || 0) + 1;
    const pair = [i.from, i.to].sort().join('↔');
    pairs[pair] = (pairs[pair] || 0) + 1;
    types[i.type] = (types[i.type] || 0) + 1;
  });

  const members = Object.keys(memberActivity);
  const avgInteractions = interactions.length / members.length;
  const strongPairs = Object.entries(pairs).filter(([_, c]) => c >= avgInteractions).sort((a, b) => b[1] - a[1]);
  const isolated = members.filter(m => memberActivity[m] < avgInteractions * 0.3);

  return {
    team_id: teamId,
    members: members.length,
    total_interactions: interactions.length,
    interaction_types: types,
    avg_interactions_per_member: Math.round(avgInteractions * 10) / 10,
    strongest_connections: strongPairs.slice(0, 5).map(([pair, count]) => ({ pair, count })),
    potentially_isolated: isolated,
    collaboration_score: Math.min(100, Math.round(
      (interactions.length / members.length * 5) +
      (strongPairs.length / members.length * 20) +
      (isolated.length === 0 ? 30 : 0)
    )),
    recommendations: [
      isolated.length > 0 && `${isolated.length} team member(s) appear isolated: ${isolated.join(', ')}`,
      !types.review && 'No code reviews detected — implement peer review process',
      !types.meeting && 'No meetings logged — ensure regular sync-ups'
    ].filter(Boolean)
  };
}

/**
 * Map influence/stakeholder relationships
 */
export async function influenceMap(people) {
  // people: [{name, connections, role, influence_score?, reach?}]
  const mapped = people.map(p => {
    const influence = p.influence_score || (p.connections || 0) * 2 + (p.reach || 0);
    let tier = 'observer';
    if (influence >= 80) tier = 'key_influencer';
    else if (influence >= 50) tier = 'influencer';
    else if (influence >= 20) tier = 'participant';

    return {
      name: p.name,
      role: p.role || 'unknown',
      connections: p.connections || 0,
      reach: p.reach || 0,
      influence_score: influence,
      tier
    };
  }).sort((a, b) => b.influence_score - a.influence_score);

  const tiers = { key_influencer: 0, influencer: 0, participant: 0, observer: 0 };
  mapped.forEach(p => tiers[p.tier]++);

  return {
    total_people: people.length,
    influence_map: mapped,
    tier_distribution: tiers,
    key_influencers: mapped.filter(p => p.tier === 'key_influencer').map(p => p.name),
    avg_influence: Math.round(mapped.reduce((s, p) => s + p.influence_score, 0) / mapped.length),
    strategy: {
      engage_first: mapped.slice(0, 3).map(p => p.name),
      grow: mapped.filter(p => p.tier === 'participant').map(p => p.name),
      watch: mapped.filter(p => p.tier === 'observer').map(p => p.name)
    }
  };
}

/**
 * Set up automated feedback collection loops
 */
export async function feedbackLoop(channel, action, data = {}) {
  if (!feedbackStore.has(channel)) feedbackStore.set(channel, []);
  const store = feedbackStore.get(channel);

  if (action === 'collect') {
    store.push({
      feedback: data.feedback,
      rating: data.rating || null,
      category: data.category || 'general',
      source: data.source || 'direct',
      timestamp: new Date().toISOString()
    });
    return { collected: true, channel, total: store.length };
  }

  if (action === 'analyze') {
    if (store.length === 0) return { channel, message: 'No feedback collected yet.' };
    const categories = {};
    const ratings = store.filter(f => f.rating != null).map(f => f.rating);
    store.forEach(f => { categories[f.category] = (categories[f.category] || 0) + 1; });

    return {
      channel,
      total_feedback: store.length,
      avg_rating: ratings.length ? Math.round(ratings.reduce((a, b) => a + b, 0) / ratings.length * 100) / 100 : null,
      category_breakdown: categories,
      recent: store.slice(-5).map(f => ({ feedback: f.feedback?.substring(0, 100), rating: f.rating, category: f.category })),
      sentiment_estimate: ratings.length
        ? (ratings.reduce((a, b) => a + b, 0) / ratings.length > 3.5 ? 'positive' : ratings.reduce((a, b) => a + b, 0) / ratings.length > 2.5 ? 'neutral' : 'negative')
        : 'unknown',
      action_items: [
        Object.keys(categories).length > 5 && 'Many categories — consider consolidating',
        ratings.length && ratings.reduce((a, b) => a + b, 0) / ratings.length < 3 && 'Below-average ratings — investigate pain points',
        store.length > 50 && 'Large feedback volume — consider automated categorization'
      ].filter(Boolean)
    };
  }

  if (action === 'configure') {
    return {
      channel,
      config: {
        triggers: data.triggers || ['post-support', 'post-purchase', 'monthly'],
        questions: data.questions || ['How would you rate your experience?', 'What could we improve?'],
        scale: data.scale || '1-5',
        follow_up: data.follow_up !== false
      },
      status: 'configured'
    };
  }

  return { hint: 'Actions: collect {feedback, rating, category}, analyze, configure {triggers, questions}' };
}
