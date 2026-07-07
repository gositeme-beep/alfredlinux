/**
 * museEngine.js — MUSE: Creative Intelligence Engine
 *
 * Brainstorming, brand voice, storytelling, name generation,
 * creative variations, marketing copy — the creative brain of Alfred.
 *
 * Intelligence Type: Creative
 * Tools: 10
 */

import crypto from 'node:crypto';

const brandProfiles = new Map(); // user → {voice, values, personality}

// ── Creative templates ──────────────────────────────────────────────────
const FRAMEWORKS = {
  brainstorm: ['SCAMPER', 'Mind Map', 'Six Thinking Hats', 'Random Association', 'Reverse Brainstorm'],
  naming: ['portmanteau', 'acronym', 'metaphor', 'alliteration', 'invented_word', 'compound'],
  storytelling: ['hero_journey', 'problem_solution', 'before_after', 'case_study', 'analogy']
};

function generateId() { return crypto.randomBytes(4).toString('hex'); }

/**
 * Generate creative ideas for a topic/problem using structured frameworks
 */
export async function brainstorm(topic, options = {}) {
  const count = options.count || 10;
  const framework = options.framework || 'free';
  const angles = [
    `What if we ${topic} but from the opposite perspective?`,
    `How would a child approach ${topic}?`,
    `What technology from 2030 would solve ${topic}?`,
    `If ${topic} were a physical product, what would it look like?`,
    `What's the laziest possible solution to ${topic}?`,
    `What would happen if we combined ${topic} with music?`,
    `How would nature solve ${topic}?`,
    `What's the $0 budget way to handle ${topic}?`,
    `If we had unlimited resources for ${topic}?`,
    `What existing solution from another industry applies to ${topic}?`,
    `What's the most fun version of ${topic}?`,
    `How would this work in 5 years? 50 years?`,
    `Who has never tried ${topic} and why?`,
    `What constraints can we remove from ${topic}?`,
    `What if ${topic} were a game — what are the rules?`
  ];
  return {
    topic,
    framework: framework === 'free' ? 'Free Association' : framework,
    ideas: angles.slice(0, count).map((idea, i) => ({
      id: i + 1,
      prompt: idea,
      category: ['lateral', 'perspective', 'futurism', 'physical', 'constraint', 'cross-domain'][i % 6]
    })),
    techniques_to_try: FRAMEWORKS.brainstorm,
    next_steps: 'Pick 2-3 ideas that excite you most, then drill deeper with follow-up brainstorms'
  };
}

/**
 * Define/analyze brand voice and tone
 */
export async function brandVoice(user, action, config = {}) {
  if (action === 'set') {
    const profile = {
      voice: config.voice || 'friendly_professional',
      values: config.values || ['innovation', 'simplicity', 'trust'],
      personality: config.personality || ['confident', 'helpful', 'warm'],
      vocabulary: config.vocabulary || { use: ['empower', 'seamless', 'smart'], avoid: ['cheap', 'basic', 'just'] },
      examples: config.examples || [],
      updatedAt: new Date().toISOString()
    };
    brandProfiles.set(user, profile);
    return { action: 'set', profile };
  }
  if (action === 'analyze') {
    const text = config.text || '';
    const profile = brandProfiles.get(user);
    const words = text.toLowerCase().split(/\s+/);
    const matchedGood = profile?.vocabulary?.use?.filter(w => words.includes(w)) || [];
    const matchedBad = profile?.vocabulary?.avoid?.filter(w => words.includes(w)) || [];
    return {
      action: 'analyze', on_brand: matchedBad.length === 0,
      matching_voice_words: matchedGood, off_brand_words: matchedBad,
      score: Math.max(0, 100 - matchedBad.length * 20 + matchedGood.length * 10),
      suggestions: matchedBad.map(w => `Replace "${w}" with a more on-brand alternative`)
    };
  }
  return { profile: brandProfiles.get(user) || null, hint: 'Use action "set" to define brand voice' };
}

/**
 * Generate compelling narratives/stories
 */
export async function storytell(topic, style = 'hero_journey') {
  const structures = {
    hero_journey: {
      name: "Hero's Journey", acts: [
        { act: 'The Ordinary World', prompt: `Describe the current state before ${topic} — what is the pain?` },
        { act: 'The Call to Adventure', prompt: `What triggers the need for change? Why now?` },
        { act: 'The Challenge', prompt: `What obstacles stand in the way of ${topic}?` },
        { act: 'The Transformation', prompt: `How does the solution change everything?` },
        { act: 'The New World', prompt: `What does life look like after ${topic} succeeds?` }
      ]
    },
    problem_solution: {
      name: 'Problem → Solution', acts: [
        { act: 'The Problem', prompt: `Paint the pain of ${topic} — make it visceral` },
        { act: 'The Failed Attempts', prompt: `What have people tried before? Why did they fail?` },
        { act: 'The Breakthrough', prompt: `What makes this approach fundamentally different?` },
        { act: 'The Proof', prompt: `Show concrete results — numbers, testimonials, demos` },
        { act: 'The Future', prompt: `What becomes possible now?` }
      ]
    },
    before_after: {
      name: 'Before & After', acts: [
        { act: 'Before', prompt: `Life without ${topic} — the struggles, the waste, the frustration` },
        { act: 'The Moment', prompt: `The exact moment of discovery / decision` },
        { act: 'After', prompt: `The transformation — specific, tangible improvements` }
      ]
    }
  };
  const structure = structures[style] || structures.hero_journey;
  return {
    topic, style: structure.name,
    narrative_framework: structure.acts,
    writing_tips: [
      'Use specific details over generalities',
      'Include sensory language (what they see, hear, feel)',
      'One character / persona the audience identifies with',
      'Conflict creates interest — don\'t skip the struggle',
      'End with a clear emotional payoff'
    ]
  };
}

/**
 * Generate creative names (products, domains, features)
 */
export async function nameGenerator(seed, type = 'product', count = 10) {
  const prefixes = ['Go', 'Pro', 'Neo', 'Zen', 'Aero', 'Nova', 'Flux', 'Vibe', 'Apex', 'Drift'];
  const suffixes = ['ly', 'io', 'ify', 'able', 'hub', 'lab', 'mind', 'flow', 'sync', 'spark'];
  const words = seed.toLowerCase().split(/\s+/);
  const root = words[0];
  const names = [];
  // Portmanteau
  for (const p of prefixes.slice(0, 3)) names.push({ name: `${p}${root.charAt(0).toUpperCase() + root.slice(1)}`, technique: 'prefix_blend' });
  // Suffix blend
  for (const s of suffixes.slice(0, 3)) names.push({ name: `${root}${s}`, technique: 'suffix_blend' });
  // Compound
  names.push({ name: `${root.toUpperCase()} AI`, technique: 'compound' });
  names.push({ name: `${root}Wave`, technique: 'compound' });
  names.push({ name: `${root.charAt(0).toUpperCase() + root.slice(1)}Stack`, technique: 'compound' });
  names.push({ name: `The ${root.charAt(0).toUpperCase() + root.slice(1)} Engine`, technique: 'definite_article' });
  return {
    seed, type,
    suggestions: names.slice(0, count),
    naming_tips: [
      'Test pronunciation — say it out loud 10 times',
      'Check domain availability for .com and .ai',
      'Search trademark databases before committing',
      'Ask 5 strangers what they think it means',
      'Shorter is almost always better'
    ]
  };
}

/**
 * Generate taglines/slogans
 */
export async function tagline(product, audience = '', benefit = '') {
  const formulas = [
    { formula: 'Verb + Outcome', example: `${product}: Build faster. Ship smarter.` },
    { formula: 'Question', example: `What if ${product} could do it all?` },
    { formula: 'Contrast', example: `${product}: Less complexity. More results.` },
    { formula: 'Bold Claim', example: `${product}: The last tool you'll ever need.` },
    { formula: 'Emotional', example: `${product}: Because your ${audience || 'business'} deserves better.` },
    { formula: 'Numbers', example: `${product}: ${benefit || '10x faster'}. Zero hassle.` },
    { formula: 'Metaphor', example: `${product}: Your digital co-pilot.` },
    { formula: 'Simplicity', example: `${product}. Just works.` }
  ];
  return { product, audience, benefit, taglines: formulas, best_practices: 'Under 8 words. One clear idea. Memorable rhythm.' };
}

/**
 * Generate creative variations of content
 */
export async function variations(content, styles = ['formal', 'casual', 'bold', 'minimal']) {
  const transforms = {
    formal: { tone: 'Professional and polished', rules: ['Full sentences', 'No contractions', 'Third person', 'Passive voice is ok'] },
    casual: { tone: 'Friendly and approachable', rules: ['Contractions welcome', 'First/second person', 'Short sentences', 'Conversational'] },
    bold: { tone: 'Confident and provocative', rules: ['Strong verbs', 'Definitive statements', 'No hedging words', 'Power words'] },
    minimal: { tone: 'Ultra-concise', rules: ['Under 10 words per sentence', 'Strip all filler', 'Core message only'] },
    poetic: { tone: 'Lyrical and evocative', rules: ['Metaphors', 'Sensory language', 'Rhythm and flow', 'Emotional resonance'] },
    technical: { tone: 'Precise and detailed', rules: ['Specific numbers', 'Industry terms', 'Logical structure', 'Evidence-based'] }
  };
  return {
    original: content.substring(0, 500),
    variations: styles.map(s => ({
      style: s,
      guidelines: transforms[s] || transforms.casual,
      prompt: `Rewrite this in a ${s} tone following these rules: ${(transforms[s] || transforms.casual).rules.join(', ')}`
    })),
    tip: 'A/B test at least 2 variations to see which resonates with your audience'
  };
}

/**
 * Generate metaphors/analogies for complex concepts
 */
export async function metaphor(concept, audience = 'general') {
  const domains = ['nature', 'cooking', 'sports', 'music', 'building', 'journey', 'gardening', 'weather'];
  const metaphors = domains.map(d => ({
    domain: d,
    prompt: `Explain "${concept}" using a ${d} metaphor for a ${audience} audience`,
    example_structure: `${concept} is like [${d} concept] because [shared trait]`
  }));
  return {
    concept, audience,
    metaphor_prompts: metaphors,
    tips: [
      'The best metaphors share one strong similarity',
      'Avoid metaphors your audience won\'t relate to',
      'Mixed metaphors confuse — stick to one domain per explanation',
      'Test: if you remove the metaphor, is the concept still clear?'
    ]
  };
}

/**
 * Generate mood board descriptions (colors, themes, aesthetics)
 */
export async function moodBoard(theme, vibe = 'modern') {
  const vibes = {
    modern: { colors: ['#1a1a2e', '#16213e', '#0f3460', '#e94560', '#533483'], typography: 'Inter / Space Grotesk', texture: 'Clean gradients, glass morphism', imagery: 'Minimal, geometric, tech-forward' },
    warm: { colors: ['#2d1b00', '#5c3d2e', '#b68d40', '#f4e285', '#f9f3e3'], typography: 'Playfair Display / Lora', texture: 'Natural textures, paper, wood grain', imagery: 'Organic, handcrafted, cozy' },
    bold: { colors: ['#000000', '#ff0054', '#ffd600', '#00e5ff', '#7b2ff7'], typography: 'Bebas Neue / Oswald', texture: 'High contrast, sharp edges, neon', imagery: 'Dynamic, energetic, urban' },
    minimal: { colors: ['#ffffff', '#f5f5f5', '#e0e0e0', '#333333', '#000000'], typography: 'Helvetica Neue / System', texture: 'White space, thin lines, subtle shadows', imagery: 'Sparse, functional, breathable' },
    nature: { colors: ['#1b4332', '#2d6a4f', '#52b788', '#95d5b2', '#d8f3dc'], typography: 'Merriweather / Source Sans', texture: 'Organic patterns, leaf veins, water', imagery: 'Landscapes, plants, natural light' },
    luxury: { colors: ['#0d0d0d', '#1a1a2e', '#b8860b', '#d4af37', '#f5f0e1'], typography: 'Didot / Cormorant Garamond', texture: 'Metallic foils, marble, silk', imagery: 'Elegance, exclusivity, craftsmanship' }
  };
  const v = vibes[vibe] || vibes.modern;
  return {
    theme, vibe,
    palette: v.colors.map((c, i) => ({ hex: c, role: ['primary', 'secondary', 'accent', 'highlight', 'background'][i] })),
    typography: v.typography, texture: v.texture, imagery_direction: v.imagery,
    css_variables: v.colors.reduce((acc, c, i) => { acc[`--color-${i+1}`] = c; return acc; }, {}),
    available_vibes: Object.keys(vibes)
  };
}

/**
 * Generate marketing/sales copy
 */
export async function copywrite(product, type = 'landing_hero', details = {}) {
  const templates = {
    landing_hero: { structure: ['Headline (5-8 words)', 'Subheadline (15-25 words)', 'CTA button text', 'Social proof line'], formula: 'AIDA (Attention → Interest → Desire → Action)' },
    email_subject: { structure: ['Curiosity gap', 'Urgency', 'Personalized', 'Question', 'Number-based'], formula: 'Open loop that demands resolution' },
    product_desc: { structure: ['1-line hook', 'Key benefit 1-3', 'Social proof', 'CTA'], formula: 'Features → Benefits → Proof → Action' },
    social_post: { structure: ['Hook (first line)', 'Story/value (2-3 lines)', 'CTA', 'Hashtags'], formula: 'Stop scroll → Deliver value → Next step' },
    ad_copy: { structure: ['Headline', 'Primary text', 'Description', 'CTA'], formula: 'Problem → Agitate → Solution → Proof → CTA' }
  };
  const t = templates[type] || templates.landing_hero;
  return {
    product, type, formula: t.formula, structure: t.structure,
    prompt: `Write ${type} copy for "${product}". ${details.audience ? 'Audience: ' + details.audience : ''}. ${details.benefit ? 'Key benefit: ' + details.benefit : ''}. Follow the ${t.formula} framework.`,
    power_words: ['Effortless', 'Proven', 'Exclusive', 'Instantly', 'Guaranteed', 'Revolutionary', 'Free', 'Limited'],
    avoid: ['Maybe', 'Try', 'Might', 'Possibly', 'Somewhat', 'Kind of']
  };
}

/**
 * Generate elevator pitch / product pitch
 */
export async function pitch(product, audience, problem, solution) {
  const formats = {
    elevator_30s: `For ${audience} who ${problem}, ${product} is a ${solution}. Unlike alternatives, we ${'{unique_differentiator}'}.`,
    investor: `${product} solves the ${'$X'} problem of ${problem} for ${audience}. We're ${solution}, with ${'[traction metric]'}, growing ${'[growth %]'} MoM.`,
    customer: `Tired of ${problem}? ${product} lets you ${solution} — in minutes, not hours. ${'{social_proof}'}.`,
    tweet: `${product}: ${solution}. For anyone who's ever ${problem}. Try it free → ${'{link}'}`,
    one_liner: `${product} = ${solution} for ${audience}`
  };
  return {
    product, audience, problem, solution,
    pitches: Object.entries(formats).map(([format, template]) => ({ format, template })),
    presentation_tips: ['Lead with the problem, not the solution', 'One specific number beats three vague claims', 'End with a clear ask']
  };
}
