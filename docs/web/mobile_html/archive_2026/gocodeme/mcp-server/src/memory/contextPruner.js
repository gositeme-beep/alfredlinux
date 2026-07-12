/**
 * contextPruner.js — Smart Memory Context Pruning
 *
 * Instead of dumping all memories above a similarity threshold into the
 * system prompt, this module ranks and prunes memories intelligently:
 *
 *  1. Semantic relevance  — cosine similarity to current conversation
 *  2. Recency decay       — newer memories get a boost
 *  3. Category priority   — preferences > project_context > session_summary > general
 *  4. Token budget        — hard cap on total injected tokens (~2000 tokens ≈ 8000 chars)
 *  5. Deduplication       — near-duplicate memories are collapsed
 *
 * The result: Alfred gets the most relevant, recent, unique memories
 * that fit within a tight token budget — no wasted prompt space.
 */

// ── Configuration ──────────────────────────────────────────────────────
const MAX_MEMORY_CHARS = 6000;    // ~1500 tokens — keeps injection lean
const MIN_SCORE = 0.25;           // lower threshold since we do our own ranking
const SIMILARITY_DEDUP = 0.92;    // memories with >92% overlap are dupes

/** Category priority weights (higher = more important to include) */
const CATEGORY_WEIGHTS = {
  'preference':       1.4,
  'preferences':      1.4,
  'project_context':  1.3,
  'instruction':      1.25,
  'correction':       1.2,
  'important':        1.2,
  'session_summary':  0.9,   // summaries are useful but verbose
  'general':          1.0,
  'fact':             1.0,
};

/**
 * Compute a recency boost for a memory.
 * Memories from the last hour get a 1.3x boost, last day 1.15x, last week 1.05x.
 *
 * @param {string} createdISO — ISO date string
 * @returns {number} — multiplier (1.0 – 1.3)
 */
function recencyBoost(createdISO) {
  if (!createdISO || createdISO === 'unknown') return 1.0;
  try {
    const ageMs = Date.now() - new Date(createdISO).getTime();
    const ageHours = ageMs / (1000 * 60 * 60);
    if (ageHours < 1) return 1.3;
    if (ageHours < 24) return 1.15;
    if (ageHours < 168) return 1.05; // 1 week
    return 1.0;
  } catch {
    return 1.0;
  }
}

/**
 * Detect near-duplicate memories using character overlap.
 * Fast heuristic — no embedding comparison needed.
 *
 * @param {string} a
 * @param {string} b
 * @returns {boolean}
 */
function isDuplicate(a, b) {
  if (!a || !b) return false;
  const shorter = a.length < b.length ? a : b;
  const longer = a.length >= b.length ? a : b;
  if (shorter.length < 20) return false;

  // If one is a substring of the other, it's a dupe
  if (longer.includes(shorter)) return true;

  // Compare normalized versions
  const normA = a.toLowerCase().replace(/\s+/g, ' ').trim();
  const normB = b.toLowerCase().replace(/\s+/g, ' ').trim();
  if (normA === normB) return true;

  // Jaccard similarity on word sets
  const setA = new Set(normA.split(' '));
  const setB = new Set(normB.split(' '));
  const intersection = [...setA].filter(w => setB.has(w)).length;
  const union = new Set([...setA, ...setB]).size;
  return union > 0 && (intersection / union) > SIMILARITY_DEDUP;
}

/**
 * Smart-prune memories for system prompt injection.
 *
 * Takes raw recall results and returns the best subset that fits
 * within the token budget, ranked by composite score.
 *
 * @param {Array<{id: string, text: string, category: string, score: number, created: string}>} memories
 * @param {object} [options]
 * @param {number} [options.maxChars]      — character budget (default: 6000)
 * @param {number} [options.maxMemories]   — hard cap on count (default: 12)
 * @param {string} [options.conversationContext] — extra context about what's being discussed
 * @returns {Array<{text: string, category: string, compositeScore: number}>}
 */
export function pruneMemories(memories, options = {}) {
  const maxChars = options.maxChars || MAX_MEMORY_CHARS;
  const maxCount = options.maxMemories || 12;

  if (!memories || memories.length === 0) return [];

  // Step 1: Filter by minimum score
  let candidates = memories.filter(m => m.score >= MIN_SCORE);

  // Step 2: Compute composite score
  candidates = candidates.map(m => {
    const categoryWeight = CATEGORY_WEIGHTS[m.category] || 1.0;
    const recency = recencyBoost(m.created);
    const compositeScore = m.score * categoryWeight * recency;
    return { ...m, compositeScore };
  });

  // Step 3: Sort by composite score (best first)
  candidates.sort((a, b) => b.compositeScore - a.compositeScore);

  // Step 4: Deduplicate — keep first (highest-ranked) occurrence
  const kept = [];
  for (const mem of candidates) {
    const isDupe = kept.some(k => isDuplicate(k.text, mem.text));
    if (!isDupe) kept.push(mem);
  }

  // Step 5: Respect token budget
  const result = [];
  let totalChars = 0;

  for (const mem of kept) {
    if (result.length >= maxCount) break;
    const entryLen = mem.text.length + mem.category.length + 10; // overhead for formatting
    if (totalChars + entryLen > maxChars) {
      // If we haven't added anything yet, add a truncated version
      if (result.length === 0) {
        result.push({
          text: mem.text.slice(0, maxChars - 50),
          category: mem.category,
          compositeScore: mem.compositeScore,
        });
      }
      break;
    }
    totalChars += entryLen;
    result.push({
      text: mem.text,
      category: mem.category,
      compositeScore: mem.compositeScore,
    });
  }

  return result;
}

/**
 * Format pruned memories into a system prompt block.
 *
 * @param {Array} prunedMemories — output from pruneMemories()
 * @returns {string}
 */
export function formatMemoryBlock(prunedMemories) {
  if (!prunedMemories || prunedMemories.length === 0) return '';

  const lines = prunedMemories.map(m => `- [${m.category}] ${m.text}`);
  return `\n## Alfred's Memory (${lines.length} relevant memories)\n${lines.join('\n')}\n`;
}
