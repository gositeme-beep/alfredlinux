/**
 * telnyxCalls.js — Telnyx Outbound Calling for Alfred Voice
 *
 * Features:
 *   - Make outbound calls to any phone number via Telnyx REST API
 *   - Track active calls per session
 *   - Hang up calls
 *   - Get call status
 *   - Webhook handler for call events (answered, hangup, etc.)
 *
 * Requires in .env:
 *   TELNYX_API_KEY=KEY019CAF8759B095A99D81936E824A5DDD_3GK2fWyWzCgBCH9eERNlUg
 *   TELNYX_FROM_NUMBER=+1XXXXXXXXXX   (your Telnyx number)
 *   TELNYX_CONNECTION_ID=             (your SIP connection or TeXML app ID)
 */

import axios from 'axios';

const TELNYX_API_KEY    = process.env.TELNYX_API_KEY    || '';
const TELNYX_FROM       = process.env.TELNYX_FROM_NUMBER || '';
const TELNYX_CONN_ID    = process.env.TELNYX_CONNECTION_ID || '';
const TELNYX_BASE       = 'https://api.telnyx.com/v2';

// Track active calls: callControlId → { to, from, status, sessionId, startedAt }
const activeCalls = new Map();

/**
 * Make an outbound call via Telnyx.
 * @param {string} to   - Destination phone number (E.164 format, e.g. +15551234567)
 * @param {string} from - Caller ID (defaults to TELNYX_FROM_NUMBER env)
 * @param {string} [sessionId] - Voice session ID to associate the call with
 * @param {object} [opts]
 * @param {string} [opts.webhookUrl] - URL for Telnyx call events (optional)
 * @param {string} [opts.sipHeader]  - Custom SIP header (optional)
 * @returns {Promise<{success:boolean, callControlId:string, message:string}>}
 */
export async function makeCall(to, from, sessionId = null, opts = {}) {
  if (!TELNYX_API_KEY) throw new Error('TELNYX_API_KEY not set in .env');
  if (!to)            throw new Error('Destination number (to) is required');

  const fromNumber = from || TELNYX_FROM;
  if (!fromNumber) throw new Error('No from number — set TELNYX_FROM_NUMBER in .env or pass it explicitly');

  // Normalize to E.164
  const toE164   = normalizeNumber(to);
  const fromE164 = normalizeNumber(fromNumber);

  const body = {
    to:            toE164,
    from:          fromE164,
    from_display_name: 'Alfred AI',
  };

  if (TELNYX_CONN_ID)       body.connection_id      = TELNYX_CONN_ID;
  if (opts.webhookUrl)       body.webhook_url        = opts.webhookUrl;
  if (opts.sipHeader)        body.sip_headers        = [opts.sipHeader];

  console.log(`[Telnyx] Calling ${toE164} from ${fromE164}...`);

  const resp = await axios.post(`${TELNYX_BASE}/calls`, body, {
    headers: {
      Authorization: `Bearer ${TELNYX_API_KEY}`,
      'Content-Type': 'application/json',
    },
  });

  const callData = resp.data?.data;
  const callControlId = callData?.call_control_id;
  const callLegId     = callData?.call_leg_id;

  if (callControlId) {
    activeCalls.set(callControlId, {
      to:          toE164,
      from:        fromE164,
      status:      'initiating',
      sessionId,
      callLegId,
      startedAt:   Date.now(),
    });
    console.log(`[Telnyx] Call initiated: ${callControlId}`);
  }

  return {
    success:        true,
    callControlId,
    callLegId,
    to:             toE164,
    from:           fromE164,
    status:         callData?.state || 'initiating',
    message:        `Call to ${toE164} initiated successfully`,
  };
}

/**
 * Hang up an active call.
 * @param {string} callControlId
 * @returns {Promise<{success:boolean, message:string}>}
 */
export async function hangupCall(callControlId) {
  if (!TELNYX_API_KEY) throw new Error('TELNYX_API_KEY not set in .env');
  if (!callControlId)  throw new Error('callControlId is required');

  await axios.post(
    `${TELNYX_BASE}/calls/${callControlId}/actions/hangup`,
    {},
    { headers: { Authorization: `Bearer ${TELNYX_API_KEY}`, 'Content-Type': 'application/json' } }
  );

  activeCalls.delete(callControlId);
  console.log(`[Telnyx] Hung up call: ${callControlId}`);

  return { success: true, message: `Call ${callControlId} ended` };
}

/**
 * Get status of a specific call from Telnyx.
 * @param {string} callControlId
 */
export async function getCallStatus(callControlId) {
  if (!TELNYX_API_KEY) throw new Error('TELNYX_API_KEY not set in .env');

  const resp = await axios.get(`${TELNYX_BASE}/calls/${callControlId}`, {
    headers: { Authorization: `Bearer ${TELNYX_API_KEY}` },
  });

  const data = resp.data?.data;
  // Update local cache
  if (activeCalls.has(callControlId)) {
    activeCalls.get(callControlId).status = data?.state;
  }

  return {
    callControlId,
    status:    data?.state,
    to:        data?.to,
    from:      data?.from,
    duration:  data?.duration_ms ? `${(data.duration_ms / 1000).toFixed(1)}s` : null,
    raw:       data,
  };
}

/**
 * List all active calls tracked in memory.
 */
export function listActiveCalls() {
  const now = Date.now();
  return Array.from(activeCalls.entries()).map(([id, c]) => ({
    callControlId: id,
    to:            c.to,
    from:          c.from,
    status:        c.status,
    sessionId:     c.sessionId,
    duration:      `${((now - c.startedAt) / 1000).toFixed(0)}s`,
  }));
}

/**
 * Handle incoming Telnyx webhook events (call.answered, call.hangup, etc.)
 * Wire this up to an Express POST route: POST /telnyx/webhook
 * @param {object} event - Telnyx event payload
 * @param {Function} [onEvent] - Optional callback(event) for real-time notification
 */
export function handleWebhook(event, onEvent) {
  const eventType = event?.data?.event_type;
  const payload   = event?.data?.payload;
  const ccId      = payload?.call_control_id;

  console.log(`[Telnyx] Webhook: ${eventType} | call=${ccId}`);

  if (ccId && activeCalls.has(ccId)) {
    const call = activeCalls.get(ccId);
    switch (eventType) {
      case 'call.initiated':   call.status = 'ringing';   break;
      case 'call.answered':    call.status = 'answered';  break;
      case 'call.hangup':
      case 'call.machine.premium.detection.ended':
        call.status = 'ended';
        activeCalls.delete(ccId);
        break;
      default:
        call.status = eventType;
    }
  }

  if (onEvent) onEvent({ eventType, callControlId: ccId, payload });
}

/**
 * Send DTMF tones on an active call (e.g. to navigate IVR menus).
 * @param {string} callControlId
 * @param {string} digits - e.g. "1#" or "0"
 */
export async function sendDTMF(callControlId, digits) {
  if (!TELNYX_API_KEY) throw new Error('TELNYX_API_KEY not set in .env');

  await axios.post(
    `${TELNYX_BASE}/calls/${callControlId}/actions/send_dtmf`,
    { digits },
    { headers: { Authorization: `Bearer ${TELNYX_API_KEY}`, 'Content-Type': 'application/json' } }
  );

  return { success: true, message: `Sent DTMF "${digits}" on call ${callControlId}` };
}

/**
 * Speak text on an active call using Telnyx TTS (Text-to-Speech).
 * @param {string} callControlId
 * @param {string} text
 * @param {string} [voice='female'] - 'male' | 'female'
 * @param {string} [language='en-US']
 */
export async function speakOnCall(callControlId, text, voice = 'female', language = 'en-US') {
  if (!TELNYX_API_KEY) throw new Error('TELNYX_API_KEY not set in .env');

  await axios.post(
    `${TELNYX_BASE}/calls/${callControlId}/actions/speak`,
    { payload: text, voice, language },
    { headers: { Authorization: `Bearer ${TELNYX_API_KEY}`, 'Content-Type': 'application/json' } }
  );

  return { success: true, message: `Speaking on call ${callControlId}: "${text.substring(0, 50)}..."` };
}

/**
 * List your Telnyx phone numbers.
 */
export async function listTelnyxNumbers() {
  if (!TELNYX_API_KEY) throw new Error('TELNYX_API_KEY not set in .env');

  const resp = await axios.get(`${TELNYX_BASE}/phone_numbers`, {
    headers: { Authorization: `Bearer ${TELNYX_API_KEY}` },
    params:  { page: { size: 50 } },
  });

  return (resp.data?.data || []).map(n => ({
    phoneNumber:  n.phone_number,
    status:       n.status,
    connectionId: n.connection_id,
    label:        n.phone_number_nickname || null,
  }));
}

/**
 * Normalize a phone number to E.164 format.
 * Adds +1 if it looks like a 10-digit North American number.
 */
function normalizeNumber(num) {
  if (!num) return num;
  let n = num.replace(/\D/g, ''); // strip non-digits
  if (n.length === 10) n = '1' + n; // add country code
  return '+' + n;
}

export default {
  makeCall,
  hangupCall,
  getCallStatus,
  listActiveCalls,
  handleWebhook,
  sendDTMF,
  speakOnCall,
  listTelnyxNumbers,
};
