/**
 * livekitService.js — LiveKit Voice Room Management
 *
 * Manages LiveKit voice rooms for real-time audio communication.
 * Since LiveKit server requires a separate binary, this module provides:
 *   1. Room management via LiveKit Server SDK (when LiveKit is running)
 *   2. Graceful fallback to the existing WebSocket voice server
 *   3. Token generation for participants
 *
 * LiveKit connection:
 *   - LIVEKIT_URL: WebSocket URL (default: ws://localhost:7880)
 *   - LIVEKIT_API_KEY: API key
 *   - LIVEKIT_API_SECRET: API secret
 */

const LIVEKIT_URL = process.env.LIVEKIT_URL || 'ws://localhost:7880';
const LIVEKIT_API_KEY = process.env.LIVEKIT_API_KEY || 'devkey';
const LIVEKIT_API_SECRET = process.env.LIVEKIT_API_SECRET || 'devsecret';

let livekitSdk = null;

/**
 * Lazy-load the LiveKit Server SDK.
 */
async function getLivekitSdk() {
  if (livekitSdk) return livekitSdk;
  try {
    livekitSdk = await import('livekit-server-sdk');
    return livekitSdk;
  } catch {
    return null;
  }
}

/**
 * Check if LiveKit is available.
 */
export async function isLivekitAvailable() {
  const sdk = await getLivekitSdk();
  if (!sdk) return { available: false, reason: 'livekit-server-sdk not installed' };

  try {
    const roomService = new sdk.RoomServiceClient(LIVEKIT_URL.replace('ws://', 'http://').replace('wss://', 'https://'), LIVEKIT_API_KEY, LIVEKIT_API_SECRET);
    await roomService.listRooms();
    return { available: true, url: LIVEKIT_URL };
  } catch (err) {
    return { available: false, reason: `LiveKit not reachable: ${err.message}`, url: LIVEKIT_URL };
  }
}

/**
 * Create a voice room.
 *
 * @param {object} opts
 * @param {string} opts.name — room name
 * @param {number} [opts.maxParticipants=10] — max participants
 * @param {number} [opts.emptyTimeout=300] — seconds before empty room is destroyed
 * @returns {Promise<object>}
 */
export async function createRoom(opts) {
  const { name, maxParticipants = 10, emptyTimeout = 300 } = opts;
  if (!name) throw new Error('room name is required');

  const sdk = await getLivekitSdk();
  if (!sdk) {
    return {
      status: 'fallback',
      message: 'LiveKit not available — using existing WebSocket voice server on :3006',
      fallbackPort: 3006,
    };
  }

  try {
    const roomService = new sdk.RoomServiceClient(
      LIVEKIT_URL.replace('ws://', 'http://').replace('wss://', 'https://'),
      LIVEKIT_API_KEY, LIVEKIT_API_SECRET,
    );

    const room = await roomService.createRoom({
      name,
      maxParticipants,
      emptyTimeout,
    });

    return {
      status: 'created',
      room: {
        name: room.name,
        sid: room.sid,
        maxParticipants: room.maxParticipants,
        createdAt: new Date().toISOString(),
      },
    };
  } catch (err) {
    return { status: 'error', error: err.message };
  }
}

/**
 * Generate a participant token for a room.
 *
 * @param {object} opts
 * @param {string} opts.roomName — room to join
 * @param {string} opts.identity — participant identity (username)
 * @param {string} [opts.name] — display name
 * @param {boolean} [opts.canPublish=true] — can publish audio
 * @returns {Promise<object>}
 */
export async function generateToken(opts) {
  const { roomName, identity, name, canPublish = true } = opts;
  if (!roomName || !identity) throw new Error('roomName and identity are required');

  const sdk = await getLivekitSdk();
  if (!sdk) {
    return { status: 'fallback', message: 'LiveKit not available — use WebSocket voice on :3006' };
  }

  try {
    const at = new sdk.AccessToken(LIVEKIT_API_KEY, LIVEKIT_API_SECRET, {
      identity,
      name: name || identity,
    });
    at.addGrant({
      roomJoin: true,
      room: roomName,
      canPublish,
      canSubscribe: true,
    });

    const token = await at.toJwt();
    return {
      status: 'success',
      token,
      roomName,
      identity,
      livekitUrl: LIVEKIT_URL,
    };
  } catch (err) {
    return { status: 'error', error: err.message };
  }
}

/**
 * List active rooms.
 */
export async function listRooms() {
  const sdk = await getLivekitSdk();
  if (!sdk) {
    return {
      status: 'fallback',
      rooms: [],
      message: 'LiveKit not available — existing voice on :3006',
    };
  }

  try {
    const roomService = new sdk.RoomServiceClient(
      LIVEKIT_URL.replace('ws://', 'http://').replace('wss://', 'https://'),
      LIVEKIT_API_KEY, LIVEKIT_API_SECRET,
    );

    const rooms = await roomService.listRooms();
    return {
      status: 'success',
      rooms: rooms.map(r => ({
        name: r.name,
        sid: r.sid,
        participants: r.numParticipants,
        maxParticipants: r.maxParticipants,
        createdAt: r.creationTime ? new Date(Number(r.creationTime) * 1000).toISOString() : null,
      })),
      total: rooms.length,
    };
  } catch (err) {
    return { status: 'error', error: err.message };
  }
}

/**
 * Get participants in a room.
 */
export async function listParticipants(roomName) {
  const sdk = await getLivekitSdk();
  if (!sdk) return { status: 'fallback', participants: [] };

  try {
    const roomService = new sdk.RoomServiceClient(
      LIVEKIT_URL.replace('ws://', 'http://').replace('wss://', 'https://'),
      LIVEKIT_API_KEY, LIVEKIT_API_SECRET,
    );

    const participants = await roomService.listParticipants(roomName);
    return {
      status: 'success',
      roomName,
      participants: participants.map(p => ({
        identity: p.identity,
        name: p.name,
        state: p.state,
        joinedAt: p.joinedAt ? new Date(Number(p.joinedAt) * 1000).toISOString() : null,
      })),
      total: participants.length,
    };
  } catch (err) {
    return { status: 'error', error: err.message };
  }
}
