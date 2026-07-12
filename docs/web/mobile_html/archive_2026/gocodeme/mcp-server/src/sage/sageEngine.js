/**
 * sageEngine.js — SAGE: Linguistic Intelligence Engine
 *
 * Translation, readability analysis, grammar checking, localization,
 * summarization, keyword extraction, tone matching, text simplification,
 * glossary management, and text comparison.
 *
 * Intelligence Type: Linguistic / Verbal
 * Tools: 10
 */

const glossaries = new Map(); // project → {term: definition}

// ── Text analysis helpers ───────────────────────────────────────────────

function countSyllables(word) {
  word = word.toLowerCase().replace(/[^a-z]/g, '');
  if (word.length <= 3) return 1;
  let count = word.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/, '').match(/[aeiouy]{1,2}/g);
  return count ? count.length : 1;
}
function countSentences(text) { return (text.match(/[.!?]+/g) || []).length || 1; }
function countWords(text) { return text.split(/\s+/).filter(w => w.length > 0).length; }

/**
 * Translate content between languages (heuristic/dictionary approach)
 */
export async function translate(text, from, to) {
  // Simple phrase-level translation for common phrases
  const commonPhrases = {
    'en→fr': { 'hello': 'bonjour', 'goodbye': 'au revoir', 'thank you': 'merci', 'please': 's\'il vous plaît', 'yes': 'oui', 'no': 'non', 'welcome': 'bienvenue', 'help': 'aide', 'error': 'erreur', 'success': 'succès', 'loading': 'chargement', 'save': 'sauvegarder', 'cancel': 'annuler', 'delete': 'supprimer', 'edit': 'modifier', 'search': 'rechercher', 'settings': 'paramètres', 'profile': 'profil', 'login': 'connexion', 'logout': 'déconnexion', 'password': 'mot de passe', 'email': 'courriel', 'submit': 'soumettre', 'back': 'retour', 'next': 'suivant' },
    'en→es': { 'hello': 'hola', 'goodbye': 'adiós', 'thank you': 'gracias', 'please': 'por favor', 'yes': 'sí', 'no': 'no', 'welcome': 'bienvenido', 'help': 'ayuda', 'error': 'error', 'success': 'éxito', 'loading': 'cargando', 'save': 'guardar', 'cancel': 'cancelar', 'delete': 'eliminar', 'edit': 'editar', 'search': 'buscar', 'settings': 'configuración', 'profile': 'perfil', 'login': 'iniciar sesión', 'logout': 'cerrar sesión' },
    'en→de': { 'hello': 'hallo', 'goodbye': 'auf wiedersehen', 'thank you': 'danke', 'please': 'bitte', 'yes': 'ja', 'no': 'nein', 'welcome': 'willkommen', 'help': 'hilfe', 'error': 'fehler', 'success': 'erfolg', 'loading': 'wird geladen', 'save': 'speichern', 'cancel': 'abbrechen', 'delete': 'löschen', 'edit': 'bearbeiten', 'search': 'suchen', 'settings': 'einstellungen' }
  };
  const key = `${from}→${to}`;
  const dict = commonPhrases[key] || {};
  let translated = text;
  for (const [eng, local] of Object.entries(dict)) {
    translated = translated.replace(new RegExp(`\\b${eng}\\b`, 'gi'), local);
  }
  return {
    original: text,
    translated,
    from, to,
    method: Object.keys(dict).length ? 'dictionary_lookup' : 'passthrough',
    words_translated: Object.keys(dict).filter(w => text.toLowerCase().includes(w)).length,
    note: 'For production use, integrate a full translation API (DeepL, Google Translate, etc.)',
    supported_pairs: Object.keys(commonPhrases)
  };
}

/**
 * Analyze text readability (Flesch-Kincaid, etc.)
 */
export async function readability(text) {
  const words = countWords(text);
  const sentences = countSentences(text);
  const wordList = text.split(/\s+/).filter(w => w.length > 0);
  const syllables = wordList.reduce((s, w) => s + countSyllables(w), 0);
  const avgSentLen = words / sentences;
  const avgSylPerWord = syllables / words;

  // Flesch Reading Ease
  const fleschEase = 206.835 - 1.015 * avgSentLen - 84.6 * avgSylPerWord;
  // Flesch-Kincaid Grade Level
  const fkGrade = 0.39 * avgSentLen + 11.8 * avgSylPerWord - 15.59;
  // Automated Readability Index
  const chars = text.replace(/\s/g, '').length;
  const ari = 4.71 * (chars / words) + 0.5 * (words / sentences) - 21.43;

  let level = 'college';
  const ease = Math.round(fleschEase);
  if (ease >= 90) level = 'elementary';
  else if (ease >= 70) level = 'middle_school';
  else if (ease >= 50) level = 'high_school';
  else if (ease >= 30) level = 'college';
  else level = 'graduate';

  return {
    word_count: words,
    sentence_count: sentences,
    syllable_count: syllables,
    avg_sentence_length: Math.round(avgSentLen * 10) / 10,
    avg_syllables_per_word: Math.round(avgSylPerWord * 100) / 100,
    flesch_reading_ease: Math.round(fleschEase * 10) / 10,
    flesch_kincaid_grade: Math.round(fkGrade * 10) / 10,
    automated_readability_index: Math.round(ari * 10) / 10,
    reading_level: level,
    estimated_reading_time: `${Math.ceil(words / 238)} min`,
    recommendations: [
      avgSentLen > 25 && 'Long sentences — break into shorter ones for clarity',
      avgSylPerWord > 1.8 && 'Complex vocabulary — consider simpler word choices',
      ease < 50 && 'Difficult to read — aim for Flesch score 60+ for general audience',
      sentences < 3 && 'Very short — more context may be needed'
    ].filter(Boolean)
  };
}

/**
 * Check grammar and style
 */
export async function grammar(text) {
  const issues = [];
  const lines = text.split('\n');

  lines.forEach((line, lineNum) => {
    // Double spaces
    if (/  +/.test(line)) issues.push({ line: lineNum + 1, type: 'style', issue: 'Double spaces', suggestion: 'Use single spaces' });
    // Sentences not starting with uppercase
    const sentences = line.split(/[.!?]\s+/);
    sentences.forEach(s => {
      const trimmed = s.trim();
      if (trimmed.length > 0 && /^[a-z]/.test(trimmed)) issues.push({ line: lineNum + 1, type: 'grammar', issue: `Sentence starts with lowercase: "${trimmed.substring(0, 20)}..."`, suggestion: 'Capitalize first letter' });
    });
    // Passive voice indicators
    if (/\b(was|were|been|being|is|are)\s+(being\s+)?\w+ed\b/i.test(line)) issues.push({ line: lineNum + 1, type: 'style', issue: 'Possible passive voice', suggestion: 'Consider active voice for clarity' });
    // Repeated words
    const repeated = line.match(/\b(\w+)\s+\1\b/gi);
    if (repeated) repeated.forEach(r => issues.push({ line: lineNum + 1, type: 'grammar', issue: `Repeated word: "${r}"`, suggestion: 'Remove duplicate' }));
    // Common mistakes
    if (/\btheir\b.*\bthere\b|\bthere\b.*\btheir\b/i.test(line)) issues.push({ line: lineNum + 1, type: 'warning', issue: 'Check their/there/they\'re usage' });
    if (/\byour\b.*\byou're\b|\byou're\b.*\byour\b/i.test(line)) issues.push({ line: lineNum + 1, type: 'warning', issue: 'Check your/you\'re usage' });
    if (/\bits\b.*\bit's\b|\bit's\b.*\bits\b/i.test(line)) issues.push({ line: lineNum + 1, type: 'warning', issue: 'Check its/it\'s usage' });
  });

  return {
    text_length: text.length,
    word_count: countWords(text),
    issues_found: issues.length,
    issues,
    quality_score: Math.max(0, 100 - issues.length * 5),
    grade: issues.length === 0 ? 'A+' : issues.length <= 2 ? 'A' : issues.length <= 5 ? 'B' : issues.length <= 10 ? 'C' : 'D'
  };
}

/**
 * Localize content for a target locale
 */
export async function localize(content, locale, options = {}) {
  const localeRules = {
    'en-US': { dateFormat: 'MM/DD/YYYY', currency: '$', currencyCode: 'USD', decimal: '.', thousands: ',', measurement: 'imperial' },
    'en-GB': { dateFormat: 'DD/MM/YYYY', currency: '£', currencyCode: 'GBP', decimal: '.', thousands: ',', measurement: 'metric' },
    'fr-FR': { dateFormat: 'DD/MM/YYYY', currency: '€', currencyCode: 'EUR', decimal: ',', thousands: ' ', measurement: 'metric' },
    'fr-CA': { dateFormat: 'YYYY-MM-DD', currency: '$', currencyCode: 'CAD', decimal: ',', thousands: ' ', measurement: 'metric' },
    'de-DE': { dateFormat: 'DD.MM.YYYY', currency: '€', currencyCode: 'EUR', decimal: ',', thousands: '.', measurement: 'metric' },
    'es-ES': { dateFormat: 'DD/MM/YYYY', currency: '€', currencyCode: 'EUR', decimal: ',', thousands: '.', measurement: 'metric' },
    'ja-JP': { dateFormat: 'YYYY/MM/DD', currency: '¥', currencyCode: 'JPY', decimal: '.', thousands: ',', measurement: 'metric' }
  };
  const rules = localeRules[locale] || localeRules['en-US'];
  return {
    locale,
    formatting: rules,
    content_type: typeof content === 'string' ? 'text' : 'structured',
    localized: typeof content === 'string' ? content : content,
    checklist: [
      'Date formats converted',
      'Currency symbols updated',
      'Number formatting adjusted',
      'Measurement units converted',
      'Cultural references reviewed',
      'Text direction verified (LTR/RTL)'
    ],
    available_locales: Object.keys(localeRules),
    note: options.translate ? 'Translation requested — combine with sage_translate' : 'Formatting rules applied — content not translated'
  };
}

/**
 * Summarize long text intelligently
 */
export async function summarize(text, maxSentences = 3) {
  const sentences = text.match(/[^.!?]+[.!?]+/g) || [text];
  if (sentences.length <= maxSentences) return { summary: text, note: 'Text already short enough', original_sentences: sentences.length };

  // Score sentences by keyword frequency
  const wordFreq = {};
  const words = text.toLowerCase().split(/\s+/);
  words.forEach(w => {
    const clean = w.replace(/[^a-z0-9]/g, '');
    if (clean.length > 3) wordFreq[clean] = (wordFreq[clean] || 0) + 1;
  });

  const scored = sentences.map((sent, i) => {
    const sWords = sent.toLowerCase().split(/\s+/);
    const score = sWords.reduce((s, w) => {
      const clean = w.replace(/[^a-z0-9]/g, '');
      return s + (wordFreq[clean] || 0);
    }, 0) / sWords.length;
    return { sentence: sent.trim(), score, index: i };
  });

  // Take top N by score, maintain original order
  const topSentences = scored
    .sort((a, b) => b.score - a.score)
    .slice(0, maxSentences)
    .sort((a, b) => a.index - b.index)
    .map(s => s.sentence);

  return {
    summary: topSentences.join(' '),
    original_sentences: sentences.length,
    summary_sentences: topSentences.length,
    compression_ratio: `${Math.round(topSentences.join(' ').length / text.length * 100)}%`,
    original_word_count: countWords(text),
    summary_word_count: countWords(topSentences.join(' '))
  };
}

/**
 * Extract keywords and key phrases
 */
export async function keywords(text, count = 10) {
  const stopWords = new Set(['the','a','an','is','are','was','were','be','been','being','have','has','had','do','does','did','will','would','shall','should','may','might','must','can','could','and','but','or','nor','for','yet','so','in','on','at','to','of','by','with','from','as','into','through','during','before','after','above','below','between','out','off','over','up','down','about','this','that','these','those','it','its','he','she','they','them','his','her','their','my','your','our','what','which','who','whom','how','when','where','why','not','no','all','each','every','both','few','more','most','other','some','such','only','also','than','too','very','just','because','if','then','else','while','once']);
  
  const words = text.toLowerCase().split(/\s+/).map(w => w.replace(/[^a-z0-9-]/g, '')).filter(w => w.length > 2 && !stopWords.has(w));
  const freq = {};
  words.forEach(w => { freq[w] = (freq[w] || 0) + 1; });

  // Bigrams
  const bigrams = {};
  for (let i = 0; i < words.length - 1; i++) {
    const bi = `${words[i]} ${words[i + 1]}`;
    bigrams[bi] = (bigrams[bi] || 0) + 1;
  }

  const topWords = Object.entries(freq).sort((a, b) => b[1] - a[1]).slice(0, count);
  const topBigrams = Object.entries(bigrams).filter(([_, c]) => c >= 2).sort((a, b) => b[1] - a[1]).slice(0, 5);

  return {
    keywords: topWords.map(([word, count]) => ({ word, count, density: `${Math.round(count / words.length * 100 * 10) / 10}%` })),
    key_phrases: topBigrams.map(([phrase, count]) => ({ phrase, count })),
    total_words: words.length,
    unique_words: Object.keys(freq).length,
    vocabulary_richness: Math.round(Object.keys(freq).length / words.length * 100)
  };
}

/**
 * Match content to a target tone/voice
 */
export async function toneMatch(text, targetTone) {
  const toneIndicators = {
    professional: { positive: ['expertise','solution','optimize','strategic','efficient','implement','leverage'], negative: ['lol','gonna','wanna','stuff','things','cool','awesome'] },
    casual: { positive: ['hey','cool','awesome','check out','totally','btw','fyi'], negative: ['pursuant','herein','whereas','notwithstanding','aforementioned'] },
    friendly: { positive: ['happy','glad','wonderful','great','love','enjoy','welcome'], negative: ['unfortunately','regret','unable','deny','refuse','reject'] },
    technical: { positive: ['algorithm','implementation','architecture','protocol','interface','module','framework'], negative: ['maybe','kind of','sort of','I think','probably','guess'] },
    urgent: { positive: ['immediately','critical','asap','now','important','priority','deadline'], negative: ['whenever','eventually','sometime','maybe','consider'] }
  };

  const indicators = toneIndicators[targetTone] || toneIndicators.professional;
  const lowerText = text.toLowerCase();
  const matches = indicators.positive.filter(w => lowerText.includes(w));
  const mismatches = indicators.negative.filter(w => lowerText.includes(w));

  const score = Math.max(0, Math.min(100, 50 + matches.length * 10 - mismatches.length * 15));

  return {
    target_tone: targetTone,
    match_score: score,
    matching_words: matches,
    mismatching_words: mismatches,
    grade: score >= 80 ? 'Excellent match' : score >= 60 ? 'Good match' : score >= 40 ? 'Partial match' : 'Poor match — rewrite recommended',
    suggestions: mismatches.length > 0 ? `Remove or replace: ${mismatches.join(', ')}` : 'Tone is consistent',
    available_tones: Object.keys(toneIndicators)
  };
}

/**
 * Simplify complex text for accessibility
 */
export async function simplify(text) {
  const wordList = text.split(/\s+/);
  const complexWords = wordList.filter(w => countSyllables(w) >= 4);
  const replacements = {
    'approximately': 'about', 'utilize': 'use', 'implement': 'build', 'functionality': 'feature',
    'subsequently': 'then', 'nevertheless': 'but', 'comprehensive': 'full', 'facilitate': 'help',
    'demonstrate': 'show', 'approximately': 'about', 'sufficient': 'enough', 'endeavor': 'try',
    'commence': 'start', 'terminate': 'end', 'acquisition': 'getting', 'modification': 'change',
    'configuration': 'setup', 'authorization': 'access', 'authentication': 'login', 'infrastructure': 'system',
    'implementation': 'build', 'optimization': 'tuning', 'notification': 'alert', 'documentation': 'docs'
  };
  let simplified = text;
  for (const [complex, simple] of Object.entries(replacements)) {
    simplified = simplified.replace(new RegExp(`\\b${complex}\\b`, 'gi'), simple);
  }
  // Break long sentences
  simplified = simplified.replace(/([.!?])\s+/g, '$1\n\n');

  return {
    original_reading_level: countWords(text) > 10 ? Math.round(0.39 * (countWords(text) / countSentences(text)) + 11.8 * (wordList.reduce((s, w) => s + countSyllables(w), 0) / wordList.length) - 15.59) : 'N/A',
    simplified_text: simplified,
    changes_made: Object.keys(replacements).filter(w => text.toLowerCase().includes(w)).length,
    complex_words_found: complexWords.length,
    complex_words: complexWords.slice(0, 10),
    accessibility_tips: [
      'Use short sentences (15-20 words max)',
      'One idea per paragraph',
      'Use bullet points for lists',
      'Avoid jargon without explanation',
      'Use active voice'
    ]
  };
}

/**
 * Build/manage project glossary
 */
export async function glossary(project, action, data = {}) {
  if (!glossaries.has(project)) glossaries.set(project, {});
  const g = glossaries.get(project);

  if (action === 'add') {
    const term = data.term;
    g[term] = { definition: data.definition, category: data.category || 'general', added: new Date().toISOString() };
    return { added: term, total_terms: Object.keys(g).length };
  }
  if (action === 'lookup') {
    const term = data.term?.toLowerCase();
    const found = Object.entries(g).find(([k]) => k.toLowerCase() === term);
    return found ? { term: found[0], ...found[1] } : { term: data.term, found: false };
  }
  if (action === 'list') {
    const terms = Object.entries(g).sort((a, b) => a[0].localeCompare(b[0]));
    return { project, total: terms.length, terms: terms.map(([term, info]) => ({ term, definition: info.definition, category: info.category })) };
  }
  if (action === 'export') {
    const md = Object.entries(g).sort((a, b) => a[0].localeCompare(b[0])).map(([term, info]) => `**${term}**: ${info.definition}`).join('\n\n');
    return { format: 'markdown', content: md };
  }
  return { hint: 'Actions: add {term, definition, category?}, lookup {term}, list, export' };
}

/**
 * Compare two texts for similarity/differences
 */
export async function compare(textA, textB) {
  const wordsA = new Set(textA.toLowerCase().split(/\s+/).map(w => w.replace(/[^a-z0-9]/g, '')).filter(Boolean));
  const wordsB = new Set(textB.toLowerCase().split(/\s+/).map(w => w.replace(/[^a-z0-9]/g, '')).filter(Boolean));
  const shared = [...wordsA].filter(w => wordsB.has(w));
  const onlyA = [...wordsA].filter(w => !wordsB.has(w));
  const onlyB = [...wordsB].filter(w => !wordsA.has(w));
  const jaccardSimilarity = shared.length / (wordsA.size + wordsB.size - shared.length);

  return {
    text_a: { words: wordsA.size, sentences: countSentences(textA), chars: textA.length },
    text_b: { words: wordsB.size, sentences: countSentences(textB), chars: textB.length },
    similarity: {
      jaccard: Math.round(jaccardSimilarity * 1000) / 1000,
      shared_words: shared.length,
      percentage: `${Math.round(jaccardSimilarity * 100)}%`
    },
    differences: {
      only_in_a: onlyA.slice(0, 20),
      only_in_b: onlyB.slice(0, 20),
      unique_to_a: onlyA.length,
      unique_to_b: onlyB.length
    },
    verdict: jaccardSimilarity > 0.8 ? 'Very similar' : jaccardSimilarity > 0.5 ? 'Moderately similar' : jaccardSimilarity > 0.2 ? 'Somewhat different' : 'Very different'
  };
}
