/**
 * cortexEngine.js — CORTEX: Advanced Reasoning & Planning Engine
 *
 * Provides structured thinking, task decomposition, decision trees,
 * goal tracking, and multi-step planning capabilities.
 *
 * Capabilities:
 *  - Task decomposition (break complex tasks into steps)
 *  - Goal tracking with progress monitoring
 *  - Decision trees and option analysis
 *  - Context window management
 *  - Reasoning chains with evidence
 *  - Priority scoring and recommendation
 */

import { randomUUID } from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';

const CORTEX_BASE = '/home/gositeme/.gocodeme/cortex';

async function ensureDir(dir) { await fs.mkdir(dir, { recursive: true }); }
async function loadJSON(file, fallback = {}) {
  try { return JSON.parse(await fs.readFile(file, 'utf8')); } catch { return fallback; }
}
async function saveJSON(file, data) {
  await ensureDir(path.dirname(file));
  await fs.writeFile(file, JSON.stringify(data, null, 2));
}

function goalsPath(user) { return path.join(CORTEX_BASE, user, 'goals.json'); }
function plansPath(user) { return path.join(CORTEX_BASE, user, 'plans.json'); }
function decisionsPath(user) { return path.join(CORTEX_BASE, user, 'decisions.json'); }
function reasoningPath(user) { return path.join(CORTEX_BASE, user, 'reasoning.json'); }

// ── Task Decomposition ──────────────────────────────────────────────────────

export async function decompose(user, task) {
  const plans = await loadJSON(plansPath(user), { plans: {} });
  const id = `plan_${randomUUID().slice(0, 8)}`;

  // Auto-decompose based on keywords
  const subtasks = [];
  const taskLower = task.description.toLowerCase();

  if (task.subtasks) {
    // User provided explicit subtasks
    for (const st of task.subtasks) {
      subtasks.push({
        id: `step_${randomUUID().slice(0, 6)}`,
        title: st.title || st,
        status: 'pending',
        priority: st.priority || 'medium',
        dependencies: st.dependencies || [],
        estimated_minutes: st.estimated_minutes || null,
        notes: st.notes || '',
      });
    }
  } else {
    // Smart decomposition hints
    if (taskLower.includes('build') || taskLower.includes('create') || taskLower.includes('implement')) {
      subtasks.push(
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Analyze requirements & constraints', status: 'pending', priority: 'high', dependencies: [], estimated_minutes: 10 },
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Design architecture/data model', status: 'pending', priority: 'high', dependencies: [], estimated_minutes: 15 },
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Implement core functionality', status: 'pending', priority: 'high', dependencies: [], estimated_minutes: 30 },
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Add error handling & validation', status: 'pending', priority: 'medium', dependencies: [], estimated_minutes: 15 },
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Write tests', status: 'pending', priority: 'medium', dependencies: [], estimated_minutes: 15 },
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Review, refine & document', status: 'pending', priority: 'low', dependencies: [], estimated_minutes: 10 },
      );
    } else if (taskLower.includes('fix') || taskLower.includes('debug') || taskLower.includes('bug')) {
      subtasks.push(
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Reproduce the issue', status: 'pending', priority: 'high', dependencies: [], estimated_minutes: 10 },
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Identify root cause', status: 'pending', priority: 'high', dependencies: [], estimated_minutes: 15 },
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Implement fix', status: 'pending', priority: 'high', dependencies: [], estimated_minutes: 15 },
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Test fix & verify', status: 'pending', priority: 'high', dependencies: [], estimated_minutes: 10 },
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Check for regressions', status: 'pending', priority: 'medium', dependencies: [], estimated_minutes: 10 },
      );
    } else {
      subtasks.push(
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Research & understand scope', status: 'pending', priority: 'high', dependencies: [], estimated_minutes: 10 },
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Plan approach', status: 'pending', priority: 'high', dependencies: [], estimated_minutes: 10 },
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Execute', status: 'pending', priority: 'high', dependencies: [], estimated_minutes: 20 },
        { id: `step_${randomUUID().slice(0, 6)}`, title: 'Verify & finalize', status: 'pending', priority: 'medium', dependencies: [], estimated_minutes: 10 },
      );
    }
  }

  plans.plans[id] = {
    id,
    title: task.title || task.description.slice(0, 100),
    description: task.description,
    subtasks,
    status: 'active',
    progress: 0,
    created: new Date().toISOString(),
    updated: new Date().toISOString(),
  };
  await saveJSON(plansPath(user), plans);

  const totalMinutes = subtasks.reduce((sum, s) => sum + (s.estimated_minutes || 0), 0);
  return {
    plan_id: id,
    subtasks: subtasks.length,
    estimated_minutes: totalMinutes,
    steps: subtasks.map(s => ({ id: s.id, title: s.title, priority: s.priority })),
    message: `Task decomposed into ${subtasks.length} steps (~${totalMinutes} min).`,
  };
}

export async function updateStep(user, planId, stepId, updates) {
  const plans = await loadJSON(plansPath(user), { plans: {} });
  const plan = plans.plans[planId];
  if (!plan) return { error: true, message: `Plan ${planId} not found.` };

  const step = plan.subtasks.find(s => s.id === stepId);
  if (!step) return { error: true, message: `Step ${stepId} not found.` };

  if (updates.status) step.status = updates.status;
  if (updates.notes) step.notes = updates.notes;
  if (updates.title) step.title = updates.title;

  // Recalculate progress
  const completed = plan.subtasks.filter(s => s.status === 'completed').length;
  plan.progress = Math.round((completed / plan.subtasks.length) * 100);
  if (plan.progress === 100) plan.status = 'completed';
  plan.updated = new Date().toISOString();

  await saveJSON(plansPath(user), plans);
  return { plan_id: planId, step_id: stepId, progress: plan.progress, message: `Step updated. Plan progress: ${plan.progress}%` };
}

export async function getPlan(user, planId) {
  const plans = await loadJSON(plansPath(user), { plans: {} });
  const plan = plans.plans[planId];
  if (!plan) return { error: true, message: `Plan ${planId} not found.` };
  return plan;
}

export async function listPlans(user) {
  const plans = await loadJSON(plansPath(user), { plans: {} });
  return {
    plans: Object.values(plans.plans).map(p => ({
      id: p.id, title: p.title, status: p.status,
      progress: p.progress, steps: p.subtasks.length,
      created: p.created, updated: p.updated,
    })),
    total: Object.keys(plans.plans).length,
    message: `${Object.keys(plans.plans).length} plan(s).`,
  };
}

// ── Goal Tracking ───────────────────────────────────────────────────────────

export async function setGoal(user, goal) {
  const goals = await loadJSON(goalsPath(user), { goals: {} });
  const id = goal.id || `goal_${randomUUID().slice(0, 8)}`;
  goals.goals[id] = {
    id,
    title: goal.title,
    description: goal.description || '',
    category: goal.category || 'project',  // project, learning, quality, performance
    target: goal.target || null,           // { metric, value } e.g. { metric: 'test_coverage', value: 80 }
    current: goal.current || 0,
    milestones: goal.milestones || [],
    status: 'active',
    priority: goal.priority || 'medium',
    deadline: goal.deadline || null,
    created: new Date().toISOString(),
    updated: new Date().toISOString(),
  };
  await saveJSON(goalsPath(user), goals);
  return { id, message: `Goal "${goal.title}" set.` };
}

export async function updateGoal(user, goalId, updates) {
  const goals = await loadJSON(goalsPath(user), { goals: {} });
  const goal = goals.goals[goalId];
  if (!goal) return { error: true, message: `Goal ${goalId} not found.` };

  if (updates.current !== undefined) goal.current = updates.current;
  if (updates.status) goal.status = updates.status;
  if (updates.notes) goal.notes = updates.notes;
  if (updates.milestone_reached) {
    goal.milestones.push({ label: updates.milestone_reached, reached: new Date().toISOString() });
  }

  // Auto-complete if target reached
  if (goal.target && goal.current >= goal.target.value) {
    goal.status = 'achieved';
  }

  goal.updated = new Date().toISOString();
  await saveJSON(goalsPath(user), goals);

  const progress = goal.target ? Math.min(100, Math.round((goal.current / goal.target.value) * 100)) : null;
  return { goal_id: goalId, status: goal.status, progress, message: `Goal "${goal.title}" updated${progress !== null ? ` (${progress}%)` : ''}.` };
}

export async function listGoals(user, category) {
  const goals = await loadJSON(goalsPath(user), { goals: {} });
  let list = Object.values(goals.goals);
  if (category) list = list.filter(g => g.category === category);
  return {
    goals: list.map(g => ({
      id: g.id, title: g.title, category: g.category,
      status: g.status, priority: g.priority,
      progress: g.target ? Math.min(100, Math.round((g.current / g.target.value) * 100)) : null,
      deadline: g.deadline,
    })),
    total: list.length,
    active: list.filter(g => g.status === 'active').length,
    achieved: list.filter(g => g.status === 'achieved').length,
    message: `${list.length} goal(s).`,
  };
}

// ── Decision Analysis ───────────────────────────────────────────────────────

export async function analyzeDecision(user, decision) {
  const decisions = await loadJSON(decisionsPath(user), { decisions: {} });
  const id = `dec_${randomUUID().slice(0, 8)}`;

  // Score each option
  const scoredOptions = (decision.options || []).map(opt => {
    let score = 50; // base score
    const pros = opt.pros || [];
    const cons = opt.cons || [];

    score += pros.length * 10;
    score -= cons.length * 8;
    if (opt.risk === 'low') score += 15;
    else if (opt.risk === 'high') score -= 15;
    if (opt.effort === 'low') score += 10;
    else if (opt.effort === 'high') score -= 10;
    if (opt.impact === 'high') score += 20;
    else if (opt.impact === 'low') score -= 5;

    return { ...opt, score: Math.max(0, Math.min(100, score)) };
  });

  scoredOptions.sort((a, b) => b.score - a.score);

  const result = {
    id,
    question: decision.question,
    context: decision.context || '',
    options: scoredOptions,
    recommendation: scoredOptions.length > 0 ? scoredOptions[0] : null,
    status: 'pending',
    decided: null,
    created: new Date().toISOString(),
  };

  decisions.decisions[id] = result;
  await saveJSON(decisionsPath(user), decisions);

  return {
    decision_id: id,
    recommendation: result.recommendation?.label,
    recommendation_score: result.recommendation?.score,
    options: scoredOptions.map(o => ({ label: o.label, score: o.score })),
    message: `Decision analyzed. Recommendation: "${result.recommendation?.label}" (score: ${result.recommendation?.score}).`,
  };
}

export async function recordDecision(user, decisionId, chosenOption, reasoning) {
  const decisions = await loadJSON(decisionsPath(user), { decisions: {} });
  const dec = decisions.decisions[decisionId];
  if (!dec) return { error: true, message: `Decision ${decisionId} not found.` };

  dec.status = 'decided';
  dec.decided = { option: chosenOption, reasoning, timestamp: new Date().toISOString() };
  await saveJSON(decisionsPath(user), decisions);
  return { message: `Decision recorded: "${chosenOption}".` };
}

export async function listDecisions(user, status) {
  const decisions = await loadJSON(decisionsPath(user), { decisions: {} });
  let list = Object.values(decisions.decisions);
  if (status) list = list.filter(d => d.status === status);
  return {
    decisions: list.map(d => ({
      id: d.id, question: d.question?.slice(0, 100), status: d.status,
      recommendation: d.recommendation?.label, decided: d.decided?.option,
    })),
    total: list.length,
    message: `${list.length} decision(s).`,
  };
}

// ── Reasoning Chain ─────────────────────────────────────────────────────────

export async function addReasoning(user, reasoning) {
  const data = await loadJSON(reasoningPath(user), { chains: {} });
  const chainId = reasoning.chain_id || `chain_${randomUUID().slice(0, 8)}`;

  if (!data.chains[chainId]) {
    data.chains[chainId] = {
      id: chainId,
      topic: reasoning.topic || 'Unnamed analysis',
      steps: [],
      conclusion: null,
      confidence: null,
      created: new Date().toISOString(),
    };
  }

  data.chains[chainId].steps.push({
    step: data.chains[chainId].steps.length + 1,
    type: reasoning.type || 'observation',  // observation, hypothesis, evidence, deduction, conclusion
    content: reasoning.content,
    evidence: reasoning.evidence || [],
    confidence: reasoning.confidence || null,
    timestamp: new Date().toISOString(),
  });

  if (reasoning.type === 'conclusion') {
    data.chains[chainId].conclusion = reasoning.content;
    data.chains[chainId].confidence = reasoning.confidence || 'medium';
  }

  await saveJSON(reasoningPath(user), data);
  return {
    chain_id: chainId,
    step: data.chains[chainId].steps.length,
    message: `Reasoning step added to chain "${data.chains[chainId].topic}".`,
  };
}

export async function getReasoningChain(user, chainId) {
  const data = await loadJSON(reasoningPath(user), { chains: {} });
  const chain = data.chains[chainId];
  if (!chain) return { error: true, message: `Reasoning chain ${chainId} not found.` };
  return chain;
}

export async function listReasoningChains(user) {
  const data = await loadJSON(reasoningPath(user), { chains: {} });
  return {
    chains: Object.values(data.chains).map(c => ({
      id: c.id, topic: c.topic, steps: c.steps.length,
      has_conclusion: !!c.conclusion, confidence: c.confidence,
    })),
    total: Object.keys(data.chains).length,
    message: `${Object.keys(data.chains).length} reasoning chain(s).`,
  };
}

// ── Priority Scoring ────────────────────────────────────────────────────────

export async function scorePriority(items) {
  const scored = items.map(item => {
    let score = 50;

    // Urgency factor
    if (item.urgency === 'critical') score += 40;
    else if (item.urgency === 'high') score += 25;
    else if (item.urgency === 'medium') score += 10;
    else if (item.urgency === 'low') score -= 10;

    // Impact factor
    if (item.impact === 'high') score += 20;
    else if (item.impact === 'medium') score += 10;
    else if (item.impact === 'low') score -= 5;

    // Effort factor (lower effort = higher priority for quick wins)
    if (item.effort === 'low') score += 15;
    else if (item.effort === 'high') score -= 10;

    // Deadline factor
    if (item.deadline) {
      const daysUntil = (new Date(item.deadline) - Date.now()) / 86400000;
      if (daysUntil < 1) score += 30;
      else if (daysUntil < 3) score += 20;
      else if (daysUntil < 7) score += 10;
    }

    // Dependencies (fewer deps = easier to start)
    if (item.dependencies?.length === 0) score += 5;
    else if (item.dependencies?.length > 3) score -= 10;

    return { ...item, priority_score: Math.max(0, Math.min(100, score)) };
  });

  scored.sort((a, b) => b.priority_score - a.priority_score);

  return {
    items: scored,
    top_priority: scored[0]?.title || null,
    message: `${scored.length} items scored and sorted by priority.`,
  };
}

// ── Context Window ──────────────────────────────────────────────────────────

export async function summarizeContext(user) {
  // Gather context from all CORTEX data
  const [goals, plans, decisions, reasoning] = await Promise.all([
    loadJSON(goalsPath(user), { goals: {} }),
    loadJSON(plansPath(user), { plans: {} }),
    loadJSON(decisionsPath(user), { decisions: {} }),
    loadJSON(reasoningPath(user), { chains: {} }),
  ]);

  const activeGoals = Object.values(goals.goals).filter(g => g.status === 'active');
  const activePlans = Object.values(plans.plans).filter(p => p.status === 'active');
  const pendingDecisions = Object.values(decisions.decisions).filter(d => d.status === 'pending');
  const openChains = Object.values(reasoning.chains).filter(c => !c.conclusion);

  return {
    active_goals: activeGoals.map(g => ({ id: g.id, title: g.title, priority: g.priority })),
    active_plans: activePlans.map(p => ({ id: p.id, title: p.title, progress: p.progress })),
    pending_decisions: pendingDecisions.map(d => ({ id: d.id, question: d.question?.slice(0, 80) })),
    open_reasoning: openChains.map(c => ({ id: c.id, topic: c.topic, steps: c.steps.length })),
    summary: {
      goals: activeGoals.length,
      plans: activePlans.length,
      decisions: pendingDecisions.length,
      reasoning: openChains.length,
    },
    message: `Context: ${activeGoals.length} goals, ${activePlans.length} plans, ${pendingDecisions.length} decisions, ${openChains.length} reasoning chains.`,
  };
}
