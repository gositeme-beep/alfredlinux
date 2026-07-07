/**
 * chunker.js — Smart Text Chunking for RAG
 *
 * Splits documents into overlapping chunks suitable for embedding.
 * Strategies:
 *   - Recursive character splitting (default, great for prose)
 *   - Paragraph-based (respects natural paragraph boundaries)
 *   - Code-aware (respects function/class boundaries)
 *   - Sentence-level (fine-grained, for Q&A)
 *
 * Each chunk includes:
 *   - text: the chunk content
 *   - index: position in the document
 *   - metadata: inherited from the document + chunk-specific info
 */

const DEFAULT_CHUNK_SIZE = 1000;  // characters
const DEFAULT_OVERLAP = 200;      // overlap between chunks

/**
 * Recursive character text splitter.
 * Tries to split on paragraph breaks, then sentences, then words, then characters.
 */
function recursiveCharacterSplit(text, chunkSize = DEFAULT_CHUNK_SIZE, overlap = DEFAULT_OVERLAP) {
  const separators = ['\n\n', '\n', '. ', ', ', ' ', ''];

  function splitRecursive(text, seps) {
    const sep = seps[0];
    const remaining = seps.slice(1);

    if (!sep) {
      // Last resort: hard character split
      return hardSplit(text, chunkSize, overlap);
    }

    const parts = text.split(sep);
    const chunks = [];
    let current = '';

    for (const part of parts) {
      const candidate = current ? current + sep + part : part;

      if (candidate.length > chunkSize && current.length > 0) {
        chunks.push(current.trim());
        // Overlap: start next chunk with the tail of the current
        const overlapText = current.slice(-overlap);
        current = overlapText + sep + part;
      } else {
        current = candidate;
      }
    }

    if (current.trim()) {
      chunks.push(current.trim());
    }

    // If any chunk is still too large, recursively split it with the next separator
    if (remaining.length > 0) {
      const result = [];
      for (const chunk of chunks) {
        if (chunk.length > chunkSize * 1.5) {
          result.push(...splitRecursive(chunk, remaining));
        } else {
          result.push(chunk);
        }
      }
      return result;
    }

    return chunks;
  }

  return splitRecursive(text, separators);
}

/**
 * Hard character split with overlap.
 */
function hardSplit(text, chunkSize, overlap) {
  const chunks = [];
  let start = 0;
  while (start < text.length) {
    chunks.push(text.slice(start, start + chunkSize));
    start += chunkSize - overlap;
  }
  return chunks;
}

/**
 * Paragraph-based splitting.
 * Respects paragraph boundaries, merges small paragraphs.
 */
function paragraphSplit(text, chunkSize = DEFAULT_CHUNK_SIZE, overlap = DEFAULT_OVERLAP) {
  const paragraphs = text.split(/\n\s*\n/).filter(p => p.trim());
  const chunks = [];
  let current = '';

  for (const para of paragraphs) {
    if (current.length + para.length + 2 > chunkSize && current.length > 0) {
      chunks.push(current.trim());
      const overlapText = current.slice(-overlap);
      current = overlapText + '\n\n' + para;
    } else {
      current = current ? current + '\n\n' + para : para;
    }
  }

  if (current.trim()) chunks.push(current.trim());
  return chunks;
}

/**
 * Code-aware splitting.
 * Tries to split on function/class definitions, then on blank lines.
 */
function codeSplit(text, chunkSize = DEFAULT_CHUNK_SIZE, overlap = DEFAULT_OVERLAP) {
  // Match common code block boundaries
  const codeBreaks = /^(?:(?:export\s+)?(?:async\s+)?(?:function|class|const|let|var|def|public|private|protected)\s|\/\*\*|###|#\s+)/m;

  const lines = text.split('\n');
  const chunks = [];
  let current = '';

  for (const line of lines) {
    const isBreak = codeBreaks.test(line) && current.length > chunkSize * 0.3;

    if ((current.length + line.length + 1 > chunkSize && current.length > 0) || isBreak) {
      if (current.trim()) chunks.push(current.trim());
      const overlapLines = current.split('\n').slice(-3).join('\n');
      current = overlapLines + '\n' + line;
    } else {
      current = current ? current + '\n' + line : line;
    }
  }

  if (current.trim()) chunks.push(current.trim());
  return chunks;
}

/**
 * Sentence-level splitting.
 * Fine-grained, good for Q&A and FAQ-style content.
 */
function sentenceSplit(text, chunkSize = DEFAULT_CHUNK_SIZE, overlap = DEFAULT_OVERLAP) {
  const sentences = text.match(/[^.!?]+[.!?]+/g) || [text];
  const chunks = [];
  let current = '';

  for (const sent of sentences) {
    if (current.length + sent.length > chunkSize && current.length > 0) {
      chunks.push(current.trim());
      const overlapText = current.slice(-overlap);
      current = overlapText + ' ' + sent.trim();
    } else {
      current = current ? current + ' ' + sent.trim() : sent.trim();
    }
  }

  if (current.trim()) chunks.push(current.trim());
  return chunks;
}

/**
 * Auto-detect the best chunking strategy based on content type.
 */
function autoDetectStrategy(text, type) {
  if (type === 'code') return 'code';
  if (type === 'markdown') return 'paragraph';
  if (text.match(/\n\n/g)?.length > 5) return 'paragraph';
  return 'recursive';
}

/**
 * Chunk a document into overlapping pieces.
 *
 * @param {string} text — the document text
 * @param {object} metadata — document metadata from ingester
 * @param {object} [opts]
 * @param {string} [opts.strategy='auto'] — 'recursive', 'paragraph', 'code', 'sentence', 'auto'
 * @param {number} [opts.chunkSize=1000] — target chunk size in characters
 * @param {number} [opts.overlap=200] — overlap between consecutive chunks
 * @returns {Array<{text: string, index: number, metadata: object}>}
 */
export function chunkDocument(text, metadata = {}, opts = {}) {
  const {
    strategy = 'auto',
    chunkSize = DEFAULT_CHUNK_SIZE,
    overlap = DEFAULT_OVERLAP,
  } = opts;

  if (!text || text.trim().length === 0) return [];

  // Skip very short documents — they become a single chunk
  if (text.length <= chunkSize) {
    return [{
      text: text.trim(),
      index: 0,
      metadata: { ...metadata, chunkIndex: 0, totalChunks: 1, strategy: 'single' },
    }];
  }

  const effectiveStrategy = strategy === 'auto'
    ? autoDetectStrategy(text, metadata.type)
    : strategy;

  let rawChunks;
  switch (effectiveStrategy) {
    case 'paragraph':
      rawChunks = paragraphSplit(text, chunkSize, overlap);
      break;
    case 'code':
      rawChunks = codeSplit(text, chunkSize, overlap);
      break;
    case 'sentence':
      rawChunks = sentenceSplit(text, chunkSize, overlap);
      break;
    case 'recursive':
    default:
      rawChunks = recursiveCharacterSplit(text, chunkSize, overlap);
      break;
  }

  // Filter out empty chunks and add metadata
  return rawChunks
    .filter(c => c.trim().length > 20) // skip tiny fragments
    .map((chunk, idx, arr) => ({
      text: chunk,
      index: idx,
      metadata: {
        ...metadata,
        chunkIndex: idx,
        totalChunks: arr.length,
        strategy: effectiveStrategy,
        chunkSize: chunk.length,
      },
    }));
}
