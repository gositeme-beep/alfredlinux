'use strict';

/**
 * Sanitise user-controlled values before interpolating into log messages.
 *
 * SECURITY (R3 L-01): Strips newlines and control characters that could
 * be used for log injection / log spoofing attacks.
 *
 * @param {*} val — value to sanitise
 * @param {number} [maxLen=200] — truncate at this length
 * @returns {string}
 */
function sanitizeForLog(val, maxLen = 200) {
  return String(val)
    .replace(/[\n\r\t\x00-\x1f\x7f]/g, '_')
    .slice(0, maxLen);
}

module.exports = sanitizeForLog;
