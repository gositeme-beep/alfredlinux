'use strict';

/**
 * DirectAdmin File Manager
 *
 * Provides high-level file operations on a customer's DirectAdmin account using
 * the File Manager REST API endpoints (DA 1.62+, Swagger-documented).
 *
 * All paths are relative to the user's home directory unless an absolute path
 * under their home is provided.  The middleware enforces that all paths stay
 * within /home/<username>/ — never outside it.
 */

const path = require('path');
const FormData = require('form-data');
const { createDAClient } = require('./client');
const logger = require('../logger');

/**
 * Resolve and validate that a requested path is safely within the user's home dir.
 * Throws if path traversal is detected.
 *
 * @param {string} daUsername
 * @param {string} requestedPath  - e.g. "public_html/index.php"
 * @returns {string}  Safe absolute path string
 */
function safePath(daUsername, requestedPath) {
  const homeDir = `/home/${daUsername}`;
  // Normalise and strip any leading slash so path.join works correctly
  const cleaned = requestedPath.replace(/^\/+/, '');
  const resolved = path.posix.normalize(path.posix.join(homeDir, cleaned));

  if (!resolved.startsWith(homeDir + '/') && resolved !== homeDir) {
    throw new Error(`Path traversal attempt detected: "${requestedPath}"`);
  }

  return resolved;
}

/**
 * Parse DA's classic URL-encoded file listing response into an array of entry objects.
 * DA returns: path=atime%3D...%26type%3Ddir&path2=...
 */
function parseDaListing(rawData) {
  if (!rawData || typeof rawData !== 'string') return [];
  const entries = [];
  const pairs = rawData.split('&');
  for (const pair of pairs) {
    const eqIdx = pair.indexOf('=');
    if (eqIdx === -1) continue;
    const entryPath = decodeURIComponent(pair.slice(0, eqIdx));
    const props = {};
    const attrs = decodeURIComponent(pair.slice(eqIdx + 1)).split('&');
    for (const attr of attrs) {
      const [k, v] = attr.split('=');
      if (k) props[k] = v !== undefined ? decodeURIComponent(v) : '';
    }
    entries.push({ path: entryPath, ...props });
  }
  return entries;
}

/**
 * List files/directories at the given path inside a customer's account.
 *
 * @param {string} daUsername
 * @param {string} [dirPath='public_html']  - Path relative to user's home
 * @returns {Promise<Array>}  Array of file/directory objects
 */
async function listFiles(daUsername, dirPath = 'public_html') {
  // SECURITY (R2-11): Validate path stays within user's home directory
  safePath(daUsername, dirPath);
  const client = createDAClient(daUsername);

  // DA expects paths with leading / (relative to user's home)
  const normalizedPath = dirPath.startsWith('/') ? dirPath : `/${dirPath}`;

  const response = await client.get('/CMD_API_FILE_MANAGER', {
    params: { path: normalizedPath },
    headers: { Accept: 'text/plain, */*' },
  });

  return parseDaListing(response.data);
}

/**
 * Read the contents of a single file.
 *
 * DA returns URL-encoded response: BODY=&DIRECTORY=...&FILENAME=...&TEXT=<content>&path=...&success=file+found
 * The actual file content is in the TEXT= field.
 *
 * @param {string} daUsername
 * @param {string} filePath  - Path relative to user's home
 * @returns {Promise<string>}  File contents as a string
 */
async function readFile(daUsername, filePath) {
  // SECURITY (R2-11): Validate path stays within user's home directory
  safePath(daUsername, filePath);
  const client = createDAClient(daUsername);

  // DA expects paths with leading /
  const normalizedPath = filePath.startsWith('/') ? filePath : `/${filePath}`;

  const response = await client.get('/CMD_API_FILE_MANAGER', {
    params: { action: 'edit', path: normalizedPath },
    responseType: 'text',
    headers: { Accept: 'text/plain, */*' },
  });

  const raw = typeof response.data === 'string' ? response.data : String(response.data);

  // Check for DA error
  if (raw.includes('error=1')) {
    throw new Error(`DA file read failed: ${raw}`);
  }

  // Parse URL-encoded response to extract TEXT= field
  const params = new URLSearchParams(raw);
  if (params.has('TEXT')) {
    return params.get('TEXT');
  }

  // Fallback: if response is JSON (newer DA versions)
  if (typeof response.data === 'object' && response.data.TEXT !== undefined) {
    return response.data.TEXT;
  }

  return raw;
}

/**
 * Binary file extensions for which we must use raw download instead of text edit.
 */
const BINARY_EXTENSIONS = new Set([
  '.png', '.jpg', '.jpeg', '.gif', '.bmp', '.ico', '.svg', '.webp', '.avif',
  '.ttf', '.otf', '.woff', '.woff2', '.eot',
  '.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx',
  '.zip', '.gz', '.tar', '.rar', '.7z', '.bz2',
  '.mp3', '.mp4', '.wav', '.ogg', '.webm', '.avi', '.mov',
  '.exe', '.dll', '.so', '.dylib',
  '.sqlite', '.db',
  '.psd', '.ai', '.sketch',
  '.class', '.pyc', '.o',
]);

/**
 * Check if a file path is likely binary based on its extension.
 */
function isBinaryFile(filePath) {
  const ext = path.posix.extname(filePath).toLowerCase();
  return BINARY_EXTENSIONS.has(ext);
}

/**
 * Download a file as a raw Buffer (for binary files).
 * Uses DA's file download endpoint instead of the text-based edit action.
 *
 * @param {string} daUsername
 * @param {string} filePath  - Path relative to user's home
 * @returns {Promise<Buffer>}  File contents as a Buffer
 */
async function readFileBinary(daUsername, filePath) {
  // SECURITY (R2-11): Validate path stays within user's home directory
  safePath(daUsername, filePath);
  const client = createDAClient(daUsername);

  const normalizedPath = filePath.startsWith('/') ? filePath : `/${filePath}`;
  const dirPart  = path.posix.dirname(normalizedPath);
  const basePart = path.posix.basename(normalizedPath);

  // DA download via CMD_API_FILE_MANAGER action=extract or direct HTTP download
  const response = await client.get('/CMD_FILE_MANAGER' + normalizedPath, {
    responseType: 'arraybuffer',
    headers: { Accept: 'application/octet-stream, */*' },
    maxRedirects: 5,
  });

  return Buffer.from(response.data);
}

/**
 * Write (create or overwrite) a file in the customer's account.
 *
 * DA's CMD_API_FILE_MANAGER upload requires multipart/form-data with:
 *   action=upload, path=<dir>, file1=<file content with filename>
 *
 * @param {string} daUsername
 * @param {string} filePath   - Path relative to user's home
 * @param {string} content    - File content
 * @returns {Promise<void>}
 */
async function writeFile(daUsername, filePath, content) {
  // SECURITY (R2-11): Validate path stays within user's home directory
  safePath(daUsername, filePath);
  const client = createDAClient(daUsername);

  // filePath is relative to home dir e.g. "public_html/index.php"
  const dirPart  = path.posix.dirname(filePath);   // e.g. "public_html"
  const basePart = path.posix.basename(filePath);  // e.g. "index.php"

  const form = new FormData();
  form.append('action', 'upload');
  form.append('path', '/' + dirPart.replace(/^\/+/, ''));
  form.append('file1', Buffer.from(content, 'utf-8'), {
    filename: basePart,
    contentType: 'application/octet-stream',
  });

  const response = await client.post('/CMD_API_FILE_MANAGER', form, {
    headers: form.getHeaders(),
  });

  // DA returns an error string on failure
  const body = typeof response.data === 'string' ? response.data : JSON.stringify(response.data);
  if (body.includes('error=1')) {
    throw new Error(`DA file write failed: ${body}`);
  }

  logger.info(`writeFile: wrote ${filePath} for user ${daUsername}`);
}

/**
 * Create a directory inside the customer's account.
 *
 * @param {string} daUsername
 * @param {string} dirPath   - Path relative to user's home
 * @returns {Promise<void>}
 */
async function createDirectory(daUsername, dirPath) {
  safePath(daUsername, dirPath);
  const client = createDAClient(daUsername);

  const params = new URLSearchParams();
  params.append('action', 'newfolder');
  params.append('path', path.posix.dirname(dirPath));
  params.append('name', path.posix.basename(dirPath));

  const response = await client.post('/CMD_API_FILE_MANAGER', params.toString());
  const body = typeof response.data === 'string' ? response.data : JSON.stringify(response.data);
  if (body.includes('error=1')) {
    throw new Error(`DA mkdir failed: ${body}`);
  }
  logger.info(`createDirectory: created ${dirPath} for user ${daUsername}`);
}

/**
 * Delete a file or directory.
 *
 * @param {string} daUsername
 * @param {string} targetPath  - Path relative to user's home
 * @returns {Promise<void>}
 */
async function deleteFile(daUsername, targetPath) {
  safePath(daUsername, targetPath);
  const client = createDAClient(daUsername);

  const dirPart  = '/' + path.posix.dirname(targetPath).replace(/^\/+/, '');
  const basePart = path.posix.basename(targetPath);

  const params = new URLSearchParams();
  params.append('action', 'multiple');
  params.append('button', 'delete');
  params.append('path', dirPart);
  params.append('select0', basePart);

  const response = await client.post('/CMD_API_FILE_MANAGER', params.toString());
  const body = typeof response.data === 'string' ? response.data : JSON.stringify(response.data);
  if (body.includes('error=1')) {
    throw new Error(`DA delete failed: ${body}`);
  }
  logger.info(`deleteFile: deleted ${targetPath} for user ${daUsername}`);
}

/**
 * Rename or move a file within the customer's account.
 *
 * @param {string} daUsername
 * @param {string} oldPath
 * @param {string} newPath
 * @returns {Promise<void>}
 */
async function renameFile(daUsername, oldPath, newPath) {
  safePath(daUsername, oldPath);
  safePath(daUsername, newPath);
  const client = createDAClient(daUsername);

  const params = new URLSearchParams();
  params.append('action', 'rename');
  params.append('path', path.posix.dirname(oldPath));
  params.append('filename', path.posix.basename(oldPath));
  params.append('newname', path.posix.basename(newPath));

  const response = await client.post('/CMD_API_FILE_MANAGER', params.toString());
  const body = typeof response.data === 'string' ? response.data : JSON.stringify(response.data);
  if (body.includes('error=1')) {
    throw new Error(`DA rename failed: ${body}`);
  }
  logger.info(`renameFile: ${oldPath} → ${newPath} for user ${daUsername}`);
}

/**
 * Get file/directory metadata (size, permissions, modified date).
 *
 * @param {string} daUsername
 * @param {string} targetPath
 * @returns {Promise<object>}
 */
async function statFile(daUsername, targetPath) {
  safePath(daUsername, targetPath);
  const client = createDAClient(daUsername);

  // List the parent directory and find our file entry
  const dirPart  = path.posix.dirname(targetPath);
  const basePart = path.posix.basename(targetPath);

  const response = await client.get('/CMD_API_FILE_MANAGER', {
    params: { path: dirPart },
    headers: { Accept: 'text/plain, */*' },
  });

  const entries = parseDaListing(response.data);
  // DA returns paths like "/public_html/file.php" — match on basename
  const entry = entries.find((e) => path.posix.basename(e.path) === basePart);
  if (!entry) throw new Error(`File not found: ${targetPath}`);
  return entry;
}

module.exports = {
  listFiles,
  readFile,
  readFileBinary,
  isBinaryFile,
  writeFile,
  createDirectory,
  deleteFile,
  renameFile,
  statFile,
  safePath,
};
