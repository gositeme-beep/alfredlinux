'use strict';

/**
 * Sanitize error messages before returning them to HTTP clients.
 *
 * Strips internal paths, connection strings, passwords, and other
 * implementation details that could aid an attacker.
 *
 * SECURITY (R3 M-01): Centralised version — replaces the inline copy
 * previously only in routes/hosting.js.
 */

// Patterns that indicate a sensitive internal detail
const SENSITIVE_PATTERNS = [
  /\/home\/[^\s'"]+/gi,            // filesystem paths
  /\/tmp\/[^\s'"]+/gi,             // temp paths
  /\/var\/[^\s'"]+/gi,             // var paths
  /\/usr\/[^\s'"]+/gi,             // system paths
  /ECONNREFUSED/i,                 // connection errors
  /ECONNRESET/i,
  /ETIMEDOUT/i,
  /ENOTFOUND/i,
  /password[=:][^\s;'"]+/gi,       // leaked passwords
  /secret[=:][^\s;'"]+/gi,         // leaked secrets
  /redis:\/\/[^\s'"]+/gi,          // Redis URIs
  /mysql:\/\/[^\s'"]+/gi,          // DB URIs
  /at\s+\S+\s+\([^)]+\)/g,        // stack trace lines
  /node_modules/i,                 // npm internals
  /127\.0\.0\.1:\d+/g,             // localhost with ports
  /::1:\d+/g,                      // IPv6 localhost
];

/**
 * Return a sanitised error string safe for client consumption.
 *
 * @param {Error|string} err  – An Error instance or raw message string
 * @returns {string}
 */
function safeError(err) {
  const msg = (err instanceof Error ? err.message : String(err)) || '';

  // Check every sensitive pattern
  for (const pat of SENSITIVE_PATTERNS) {
    pat.lastIndex = 0; // reset regex state for /g patterns
    if (pat.test(msg)) {
      return 'An internal error occurred';
    }
  }

  // Clamp length to prevent overly verbose error strings
  if (msg.length > 200) {
    return 'An internal error occurred';
  }

  return msg;
}

module.exports = safeError;
