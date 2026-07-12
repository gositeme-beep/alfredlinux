/**
 * empathyEngine.js — EMPATHY: Emotional Intelligence Engine
 *
 * Gives Alfred the ability to understand, track, and respond to human emotions.
 * Sentiment analysis, tone detection, frustration sensing, de-escalation,
 * and emotional rapport building — making Alfred the world's first
 * emotionally intelligent hosting assistant.
 *
 * Intelligence Type: Emotional (EQ)
 * Tools: 11
 */

import { promises as fs } from 'node:fs';
import path from 'node:path';

// ── In-memory stores (per-user, production would use Redis) ──────────────
const moodHistory = new Map();   // user → [{ts, mood, score, context}]
const toneSettings = new Map();  // user → {tone, updatedAt}
const rapportScores = new Map(); // user → {score, interactions, lastSeen}

// ── Sentiment lexicons ──────────────────────────────────────────────────
const POSITIVE_WORDS = new Set([
  'great','awesome','love','excellent','amazing','perfect','wonderful','fantastic',
  'happy','glad','thanks','thank','please','appreciate','beautiful','brilliant',
  'cool','nice','good','best','helpful','incredible','outstanding','superb','yes',
  'works','working','solved','fixed','success','easy','fast','smooth','clean'
]);
const NEGATIVE_WORDS = new Set([
  'bad','terrible','awful','hate','broken','error','fail','failed','crash','bug',
  'slow','terrible','worst','frustrated','angry','annoyed','disappointed','stuck',
  'confused','wrong','issue','problem','help','urgent','critical','down','dead',
  'disaster','horrible','nightmare','useless','stupid','ridiculous','impossible'
]);
const FRUSTRATION_MARKERS = new Set([
  'still','again','already','never','always','why','seriously','come on','wtf',
  'not working','broken again','hours','days','tried everything','give up',
  'nothing works','same error','same problem','how many times','fed up'
]);

function analyzeWords(text) {
  const words = text.toLowerCase().replace(/[^\w\s]/g, '').split(/\s+/);
  let pos = 0, neg = 0, frust = 0;
  const lc = text.toLowerCase();
  for (const w of words) {
    if (POSITIVE_WORDS.has(w)) pos++;
    if (NEGATIVE_WORDS.has(w)) neg++;
  }
  for (const marker of FRUSTRATION_MARKERS) {
    if (lc.includes(marker)) frust++;
  }
  return { pos, neg, frust, total: words.length };
}

/**
 * Analyze sentiment of text → positive/negative/neutral + score
 */
export async function analyzeSentiment(text) {
  const { pos, neg, total } = analyzeWords(text);
  const score = total > 0 ? ((pos - neg) / Math.max(total * 0.3, 1)) : 0;
  const clampedScore = Math.max(-1, Math.min(1, score));
  let sentiment = 'neutral';
  if (clampedScore > 0.2) sentiment = 'positive';
  else if (clampedScore < -0.2) sentiment = 'negative';
  const confidence = Math.min(1, (pos + neg) / Math.max(total * 0.15, 1));
  return {
    sentiment,
    score: Math.round(clampedScore * 100) / 100,
    confidence: Math.round(confidence * 100) / 100,
    positive_signals: pos,
    negative_signals: neg,
    word_count: total,
    summary: sentiment === 'positive' ? 'User appears satisfied and positive.' :
             sentiment === 'negative' ? 'User appears dissatisfied or concerned.' :
             'User tone is neutral / informational.'
  };
}

/**
 * Detect emotional tone (angry, happy, confused, frustrated, neutral, anxious, grateful)
 */
export async function detectTone(text) {
  const { pos, neg, frust, total } = analyzeWords(text);
  const lc = text.toLowerCase();
  const hasQuestionMarks = (text.match(/\?/g) || []).length;
  const hasExclamation = (text.match(/!/g) || []).length;
  const hasAllCaps = (text.match(/[A-Z]{3,}/g) || []).length;
  const tones = [];
  if (frust >= 2 || (neg >= 3 && hasExclamation >= 1)) tones.push({ tone: 'frustrated', intensity: Math.min(1, frust / 4) });
  if (neg >= 2 && hasAllCaps >= 1) tones.push({ tone: 'angry', intensity: Math.min(1, (neg + hasAllCaps) / 6) });
  if (hasQuestionMarks >= 2 || lc.includes('how do i') || lc.includes('what is') || lc.includes("don't understand"))
    tones.push({ tone: 'confused', intensity: Math.min(1, hasQuestionMarks / 3) });
  if (lc.includes('asap') || lc.includes('urgent') || lc.includes('deadline') || lc.includes('worried'))
    tones.push({ tone: 'anxious', intensity: 0.7 });
  if (pos >= 2 || lc.includes('thank') || lc.includes('appreciate'))
    tones.push({ tone: 'grateful', intensity: Math.min(1, pos / 4) });
  if (pos >= 3 && neg === 0) tones.push({ tone: 'happy', intensity: Math.min(1, pos / 5) });
  if (tones.length === 0) tones.push({ tone: 'neutral', intensity: 0.5 });
  tones.sort((a, b) => b.intensity - a.intensity);
  return {
    primary_tone: tones[0].tone,
    intensity: Math.round(tones[0].intensity * 100) / 100,
    all_tones: tones,
    signals: { questions: hasQuestionMarks, exclamations: hasExclamation, all_caps: hasAllCaps, frustration_markers: frust }
  };
}

/**
 * Track user mood over time
 */
export async function trackMood(user, text, context = '') {
  const sentiment = await analyzeSentiment(text);
  const tone = await detectTone(text);
  const entry = {
    ts: new Date().toISOString(),
    mood: tone.primary_tone,
    sentiment: sentiment.sentiment,
    score: sentiment.score,
    context: context || text.substring(0, 100)
  };
  if (!moodHistory.has(user)) moodHistory.set(user, []);
  const history = moodHistory.get(user);
  history.push(entry);
  if (history.length > 100) history.splice(0, history.length - 100);
  // Update rapport
  updateRapport(user, sentiment.score);
  return { tracked: true, current_mood: entry, history_length: history.length };
}

/**
 * Get mood history for a user
 */
export async function getMoodHistory(user, limit = 20) {
  const history = moodHistory.get(user) || [];
  const recent = history.slice(-limit);
  const avgScore = recent.length > 0 ? recent.reduce((s, e) => s + e.score, 0) / recent.length : 0;
  const moodCounts = {};
  recent.forEach(e => { moodCounts[e.mood] = (moodCounts[e.mood] || 0) + 1; });
  const dominantMood = Object.entries(moodCounts).sort((a, b) => b[1] - a[1])[0];
  return {
    user,
    entries: recent,
    total_tracked: history.length,
    average_score: Math.round(avgScore * 100) / 100,
    dominant_mood: dominantMood ? dominantMood[0] : 'unknown',
    trend: recent.length >= 3
      ? recent.slice(-3).reduce((s, e) => s + e.score, 0) / 3 > avgScore ? 'improving' : 'declining'
      : 'insufficient_data'
  };
}

/**
 * Suggest empathetic response based on detected emotion
 */
export async function suggestResponse(text, context = '') {
  const tone = await detectTone(text);
  const sentiment = await analyzeSentiment(text);
  const strategies = {
    frustrated: {
      approach: 'Acknowledge frustration first, then offer concrete solution',
      opening: "I completely understand your frustration — let me fix this for you right now.",
      style: 'Direct, solution-oriented, no fluff',
      avoid: 'Dismissive language, asking them to repeat info, generic apologies'
    },
    angry: {
      approach: 'Validate emotion, take ownership, escalate if needed',
      opening: "I hear you, and I take this seriously. Let me personally make sure this gets resolved.",
      style: 'Calm, authoritative, empowering',
      avoid: 'Defensiveness, blame, minimizing their experience'
    },
    confused: {
      approach: 'Simplify, use analogies, offer step-by-step guidance',
      opening: "Great question — let me walk you through this step by step.",
      style: 'Patient, clear, educational',
      avoid: 'Jargon, assumptions about knowledge level, rushing'
    },
    anxious: {
      approach: 'Reassure, provide timeline, set clear expectations',
      opening: "Don't worry — I've got this covered. Here's exactly what's happening...",
      style: 'Calming, confident, specific',
      avoid: 'Uncertainty, vague timelines, adding more concerns'
    },
    grateful: {
      approach: 'Warmly acknowledge, reinforce positive experience',
      opening: "That's wonderful to hear! I'm glad I could help.",
      style: 'Warm, appreciative, encouraging',
      avoid: 'Being dismissive of their gratitude'
    },
    happy: {
      approach: 'Match energy, celebrate with them, suggest next steps',
      opening: "That's awesome! Love seeing things come together.",
      style: 'Enthusiastic, celebratory, forward-looking',
      avoid: 'Dampening their excitement, being overly formal'
    },
    neutral: {
      approach: 'Professional, efficient, informative',
      opening: "Sure thing — here's what you need to know.",
      style: 'Clear, concise, helpful',
      avoid: 'Over-compensating with emotion'
    }
  };
  const strategy = strategies[tone.primary_tone] || strategies.neutral;
  return {
    detected_tone: tone.primary_tone,
    intensity: tone.intensity,
    sentiment: sentiment.sentiment,
    recommended_strategy: strategy,
    emotional_context: {
      is_escalation_needed: tone.primary_tone === 'angry' && tone.intensity > 0.8,
      patience_level: tone.primary_tone === 'frustrated' ? 'low' : tone.primary_tone === 'confused' ? 'medium' : 'high',
      warmth_needed: ['frustrated', 'angry', 'anxious'].includes(tone.primary_tone) ? 'high' : 'normal'
    }
  };
}

/**
 * Detect frustration level (0-10 scale)
 */
export async function detectFrustration(user, text) {
  const { neg, frust, total } = analyzeWords(text);
  const lc = text.toLowerCase();
  let level = 0;
  level += Math.min(3, frust);               // frustration markers (max +3)
  level += Math.min(2, neg / 2);             // negativity (max +2)
  level += (lc.match(/!{2,}/g) || []).length > 0 ? 1 : 0;  // double exclamation
  level += (text.match(/[A-Z]{4,}/g) || []).length > 0 ? 1 : 0; // SHOUTING
  level += lc.includes('still not') || lc.includes('again') ? 1 : 0;
  level += lc.includes('give up') || lc.includes('cancel') ? 2 : 0;
  level = Math.min(10, Math.round(level));
  // Check history for escalation pattern
  const history = moodHistory.get(user) || [];
  const recentNeg = history.slice(-5).filter(e => e.sentiment === 'negative').length;
  if (recentNeg >= 3) level = Math.min(10, level + 2);
  const risk = level <= 3 ? 'low' : level <= 6 ? 'medium' : level <= 8 ? 'high' : 'critical';
  return {
    frustration_level: level,
    risk,
    escalating: recentNeg >= 3,
    recommendation: risk === 'critical' ? 'Immediate human escalation recommended' :
                    risk === 'high' ? 'Prioritize resolution, offer direct contact' :
                    risk === 'medium' ? 'Acknowledge frustration, provide clear timeline' :
                    'Normal interaction, no intervention needed',
    signals: { negative_words: neg, frustration_markers: frust, recent_negative_mood: recentNeg }
  };
}

/**
 * Generate de-escalation response for upset user
 */
export async function deescalate(text, issue = '') {
  const tone = await detectTone(text);
  const frustration = await detectFrustration('_anon', text);
  const templates = {
    low: `I understand. Let me help you with ${issue || 'this'}. Here's what I'll do...`,
    medium: `I hear your concern, and I want to make this right. Let me look into ${issue || 'this'} immediately and get you a solution.`,
    high: `I completely understand your frustration, and I'm sorry you're dealing with this. I'm making ${issue || 'this'} my top priority right now. Here's my plan to fix it...`,
    critical: `I sincerely apologize for this experience. This is unacceptable, and I take full responsibility. I'm escalating ${issue || 'this'} right now and will personally follow up. You'll hear back within the hour.`
  };
  return {
    frustration_level: frustration.frustration_level,
    risk: frustration.risk,
    de_escalation_response: templates[frustration.risk],
    techniques_used: [
      frustration.risk !== 'low' && 'Active listening acknowledgment',
      frustration.frustration_level >= 5 && 'Ownership & accountability',
      frustration.frustration_level >= 7 && 'Specific action commitment',
      frustration.frustration_level >= 9 && 'Escalation & follow-up promise'
    ].filter(Boolean),
    tone_guidance: {
      pace: frustration.frustration_level >= 7 ? 'slow_and_deliberate' : 'normal',
      formality: frustration.frustration_level >= 8 ? 'formal' : 'warm_professional',
      empathy_level: frustration.risk
    }
  };
}

/**
 * Analyze feedback/review sentiment at scale
 */
export async function analyzeFeedback(items) {
  const results = [];
  let totalScore = 0;
  const toneCounts = {};
  for (const item of items) {
    const text = typeof item === 'string' ? item : item.text || item.feedback || '';
    const sentiment = await analyzeSentiment(text);
    const tone = await detectTone(text);
    results.push({
      text: text.substring(0, 150),
      sentiment: sentiment.sentiment,
      score: sentiment.score,
      tone: tone.primary_tone
    });
    totalScore += sentiment.score;
    toneCounts[tone.primary_tone] = (toneCounts[tone.primary_tone] || 0) + 1;
  }
  const avg = results.length > 0 ? totalScore / results.length : 0;
  const positiveCount = results.filter(r => r.sentiment === 'positive').length;
  const negativeCount = results.filter(r => r.sentiment === 'negative').length;
  return {
    total_analyzed: results.length,
    average_score: Math.round(avg * 100) / 100,
    sentiment_breakdown: {
      positive: positiveCount,
      neutral: results.length - positiveCount - negativeCount,
      negative: negativeCount,
      positive_pct: Math.round((positiveCount / Math.max(results.length, 1)) * 100)
    },
    dominant_tone: Object.entries(toneCounts).sort((a, b) => b[1] - a[1])[0]?.[0] || 'unknown',
    tone_distribution: toneCounts,
    items: results,
    health: avg > 0.3 ? 'healthy' : avg > 0 ? 'needs_attention' : 'concerning'
  };
}

/**
 * Get emotional summary for a user/project
 */
export async function emotionalSummary(user) {
  const history = moodHistory.get(user) || [];
  if (history.length === 0) return { user, summary: 'No emotional data tracked yet.', entries: 0 };
  const moodCounts = {};
  let totalScore = 0;
  history.forEach(e => {
    moodCounts[e.mood] = (moodCounts[e.mood] || 0) + 1;
    totalScore += e.score;
  });
  const avgScore = totalScore / history.length;
  const recent = history.slice(-10);
  const recentAvg = recent.reduce((s, e) => s + e.score, 0) / recent.length;
  const rapport = rapportScores.get(user) || { score: 50, interactions: 0 };
  return {
    user,
    total_interactions: history.length,
    average_sentiment: Math.round(avgScore * 100) / 100,
    recent_sentiment: Math.round(recentAvg * 100) / 100,
    trend: recentAvg > avgScore + 0.1 ? 'improving' : recentAvg < avgScore - 0.1 ? 'declining' : 'stable',
    mood_distribution: moodCounts,
    dominant_mood: Object.entries(moodCounts).sort((a, b) => b[1] - a[1])[0][0],
    rapport_score: rapport.score,
    relationship_health: rapport.score >= 70 ? 'strong' : rapport.score >= 40 ? 'developing' : 'needs_attention',
    recommendations: [
      recentAvg < -0.2 && 'User sentiment declining — increase empathy in responses',
      rapport.score < 40 && 'Low rapport — prioritize positive interactions',
      moodCounts['frustrated'] > 3 && 'Frequent frustration detected — review common pain points'
    ].filter(Boolean)
  };
}

/**
 * Set Alfred's response tone preference
 */
export async function setTone(user, tone) {
  const validTones = ['professional', 'friendly', 'casual', 'empathetic', 'technical', 'cheerful', 'concise'];
  if (!validTones.includes(tone)) {
    return { error: `Invalid tone. Choose from: ${validTones.join(', ')}` };
  }
  toneSettings.set(user, { tone, updatedAt: new Date().toISOString() });
  const toneDescriptions = {
    professional: 'Clear, polished, business-appropriate responses',
    friendly: 'Warm, approachable, conversational with personality',
    casual: 'Relaxed, informal, like chatting with a colleague',
    empathetic: 'Extra warmth, validation, emotional awareness',
    technical: 'Precise, detailed, code-focused with minimal fluff',
    cheerful: 'Upbeat, enthusiastic, encouraging',
    concise: 'Minimum words, maximum info, no filler'
  };
  return { tone, description: toneDescriptions[tone], applied_for: user, updated_at: toneSettings.get(user).updatedAt };
}

/**
 * Calculate rapport/relationship score with user
 */
export async function rapportScore(user) {
  const rapport = rapportScores.get(user) || { score: 50, interactions: 0, lastSeen: null, positiveStreak: 0, negativeStreak: 0 };
  const history = moodHistory.get(user) || [];
  const healthLabel = rapport.score >= 80 ? 'excellent' : rapport.score >= 60 ? 'good' :
                      rapport.score >= 40 ? 'developing' : rapport.score >= 20 ? 'strained' : 'critical';
  return {
    user,
    rapport_score: Math.round(rapport.score),
    health: healthLabel,
    total_interactions: rapport.interactions,
    last_interaction: rapport.lastSeen,
    positive_streak: rapport.positiveStreak,
    negative_streak: rapport.negativeStreak,
    emotional_history_entries: history.length,
    tips: [
      rapport.score < 50 && 'Prioritize resolving their issues quickly',
      rapport.negativeStreak >= 3 && 'Multiple negative interactions — consider a proactive check-in',
      rapport.score >= 80 && 'Strong relationship — this user trusts Alfred'
    ].filter(Boolean)
  };
}

// ── Internal helpers ─────────────────────────────────────────────────────
function updateRapport(user, sentimentScore) {
  const r = rapportScores.get(user) || { score: 50, interactions: 0, lastSeen: null, positiveStreak: 0, negativeStreak: 0 };
  r.interactions++;
  r.lastSeen = new Date().toISOString();
  if (sentimentScore > 0.1) {
    r.score = Math.min(100, r.score + 2);
    r.positiveStreak++;
    r.negativeStreak = 0;
  } else if (sentimentScore < -0.1) {
    r.score = Math.max(0, r.score - 3);
    r.negativeStreak++;
    r.positiveStreak = 0;
  }
  rapportScores.set(user, r);
}
