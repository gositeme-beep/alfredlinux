/**
 * ragPipeline.js — Full RAG Orchestration Pipeline
 *
 * End-to-end Retrieval-Augmented Generation:
 *   1. Ingest: File / URL / raw text → parsed text
 *   2. Chunk: Smart splitting with overlaps
 *   3. Embed: Local ONNX embeddings (all-MiniLM-L6-v2)
 *   4. Store: Named vector collection
 *   5. Query: Embed query → vector search → rerank (optional) → synthesize answer
 *
 * Integrates:
 *   - documentIngester.js (parsing)
 *   - chunker.js (splitting)
 *   - vectorCollection.js (storage)
 *   - embeddings.js (local ONNX)
 *   - togetherClient.js (rerank + answer synthesis)
 */

import { ingestFile, ingestUrl, ingestText } from './documentIngester.js';
import { chunkDocument } from './chunker.js';
import {
  getCollection, addChunks, searchCollection,
  listCollections, deleteCollection, deleteDocumentBySource,
  getCollectionStats,
} from './vectorCollection.js';
import { embed, embedOne } from '../embeddings.js';
import { rerank, chatCompletion } from '../togetherClient.js';

/**
 * Ingest a document into a RAG collection.
 *
 * @param {object} opts
 * @param {string} opts.collection — collection name
 * @param {string} [opts.description] — collection description (on first create)
 * @param {string} [opts.filePath] — local file path to ingest
 * @param {string} [opts.url] — URL to ingest
 * @param {string} [opts.text] — raw text to ingest
 * @param {string} [opts.textName] — name for raw text input
 * @param {string} [opts.strategy='auto'] — chunking strategy
 * @param {number} [opts.chunkSize=1000] — chunk size in characters
 * @param {number} [opts.overlap=200] — overlap between chunks
 * @returns {Promise<object>}
 */
export async function ragIngest(opts) {
  const {
    collection,
    description = '',
    filePath,
    url,
    text: rawText,
    textName = 'manual-input',
    strategy = 'auto',
    chunkSize = 1000,
    overlap = 200,
  } = opts;

  if (!collection) throw new Error('collection name is required');

  const start = Date.now();

  // 1. Parse the document
  let doc;
  if (filePath) {
    doc = await ingestFile(filePath);
  } else if (url) {
    doc = await ingestUrl(url);
  } else if (rawText) {
    doc = ingestText(rawText, textName);
  } else {
    throw new Error('Provide one of: filePath, url, or text');
  }

  // 2. Chunk the document
  const chunks = chunkDocument(doc.text, doc.metadata, { strategy, chunkSize, overlap });

  if (chunks.length === 0) {
    return {
      status: 'empty',
      message: 'Document produced no chunks (possibly empty)',
      timing: Date.now() - start,
    };
  }

  // 3. Embed all chunks
  const chunkTexts = chunks.map(c => c.text);
  const embeddings = await embed(chunkTexts);

  // 4. Store in collection
  await getCollection(collection, description);
  const { added } = await addChunks(collection, chunks, embeddings);

  return {
    status: 'success',
    collection,
    source: doc.metadata.source,
    type: doc.metadata.type,
    documentSize: doc.metadata.size,
    chunksCreated: added,
    strategy: chunks[0]?.metadata.strategy || strategy,
    timing: Date.now() - start,
  };
}

/**
 * Query a RAG collection.
 *
 * @param {object} opts
 * @param {string} opts.collection — collection name to query
 * @param {string} opts.query — natural language query
 * @param {number} [opts.topK=10] — number of chunks to retrieve
 * @param {boolean} [opts.useReranking=true] — rerank with Together.ai Mxbai-Rerank-Large-V2
 * @param {boolean} [opts.synthesize=true] — synthesize an answer using LLM
 * @param {string} [opts.model='default'] — LLM model for answer synthesis
 * @returns {Promise<object>}
 */
export async function ragQuery(opts) {
  const {
    collection,
    query,
    topK = 10,
    useReranking = true,
    synthesize = true,
    model = 'default',
  } = opts;

  if (!collection) throw new Error('collection name is required');
  if (!query) throw new Error('query is required');

  const start = Date.now();

  // 1. Embed the query
  const queryVector = await embedOne(query);

  // 2. Vector similarity search
  let results = await searchCollection(collection, queryVector, useReranking ? topK * 3 : topK);

  // 3. Rerank with Together.ai (optional but recommended)
  if (useReranking && results.length > 0) {
    try {
      const documents = results.map(r => r.text);
      const reranked = await rerank(query, documents, 'mixedbread-ai/Mxbai-Rerank-Large-V2', topK);

      // Reorder results based on reranking scores
      results = reranked.results.map(r => ({
        ...results[r.index],
        rerankScore: r.relevance_score,
      }));
    } catch (err) {
      // Reranking failed — fall back to vector similarity order
      results = results.slice(0, topK);
    }
  }

  // 4. Synthesize answer (optional)
  let answer = null;
  if (synthesize && results.length > 0) {
    const context = results
      .slice(0, 5) // use top 5 chunks for synthesis
      .map((r, i) => `[${i + 1}] ${r.text}`)
      .join('\n\n');

    const messages = [
      {
        role: 'system',
        content: `You are a helpful assistant. Answer the user's question based ONLY on the provided context. If the context doesn't contain enough information to answer, say so. Cite sources using [1], [2], etc.`,
      },
      {
        role: 'user',
        content: `Context:\n${context}\n\nQuestion: ${query}`,
      },
    ];

    try {
      const completion = await chatCompletion(messages, model, 2048, 0.3);
      answer = completion.text;
    } catch (err) {
      answer = `(Synthesis failed: ${err.message}. See retrieved chunks below.)`;
    }
  }

  return {
    status: 'success',
    collection,
    query,
    answer,
    sources: results.map(r => ({
      text: r.text.slice(0, 300) + (r.text.length > 300 ? '...' : ''),
      score: r.score,
      rerankScore: r.rerankScore || null,
      source: r.metadata?.source || 'unknown',
      chunkIndex: r.metadata?.chunkIndex,
    })),
    totalResults: results.length,
    timing: Date.now() - start,
  };
}

/**
 * List all RAG collections.
 * @returns {Promise<object>}
 */
export async function ragListCollections() {
  const collections = await listCollections();
  return {
    status: 'success',
    collections,
    total: collections.length,
  };
}

/**
 * Delete a RAG collection or a document within one.
 * @param {object} opts
 * @param {string} opts.collection — collection to delete
 * @param {string} [opts.source] — if provided, delete only docs from this source
 * @returns {Promise<object>}
 */
export async function ragDelete(opts) {
  const { collection, source } = opts;

  if (!collection) throw new Error('collection name is required');

  if (source) {
    const deleted = await deleteDocumentBySource(collection, source);
    return {
      status: 'success',
      action: 'delete_document',
      collection,
      source,
      chunksDeleted: deleted,
    };
  }

  const deleted = await deleteCollection(collection);
  return {
    status: deleted ? 'success' : 'not_found',
    action: 'delete_collection',
    collection,
    deleted,
  };
}
