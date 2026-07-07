// Load environment variables from appropriate file
const envFile = process.env.NODE_ENV === 'production' ? '.env.production' : '.env.local';
require('dotenv').config({ path: envFile });

// Workaround for Next.js 15.3.3 Watchpack issue
process.env.NODE_ENV = process.env.NODE_ENV || 'development';

const { createServer } = require('https');
const { createServer: createHttpServer } = require('http');
const { parse } = require('url');
const fs = require('fs');
const path = require('path');
const next = require('next');
const WebSocket = require('ws');
const os = require('os');

const dev = process.env.NODE_ENV !== 'production';
const hostname = process.env.HOSTNAME || '0.0.0.0'; // Allow network access
const port = process.env.PORT || 3000;
const httpsPort = process.env.HTTPS_PORT || 3443;

// Global variables for WebSocket management
global.wsClients = new Map();
global.wsRoomSubscriptions = new Map();
global.wsUserPresence = new Map();

// Helper function to get network IP
const getNetworkIP = () => {
  const interfaces = os.networkInterfaces();
  for (const name of Object.keys(interfaces)) {
    for (const iface of interfaces[name]) {
      if (iface.family === 'IPv4' && !iface.internal) {
        return iface.address;
      }
    }
  }
  return 'localhost';
};

// HTTPS configuration
const getHttpsOptions = () => {
  try {
    // Try network certificates first (supports IP addresses)
    const networkKeyPath = path.join(__dirname, 'certificates', 'network-key.pem');
    const networkCertPath = path.join(__dirname, 'certificates', 'network-cert.pem');
    
    if (fs.existsSync(networkKeyPath) && fs.existsSync(networkCertPath)) {
      console.log('🌐 Using network certificates (supports IP addresses)');
      return {
        key: fs.readFileSync(networkKeyPath),
        cert: fs.readFileSync(networkCertPath)
      };
    }
    
    // Fallback to localhost certificates
    const keyPath = path.join(__dirname, 'certificates', 'localhost-key.pem');
    const certPath = path.join(__dirname, 'certificates', 'localhost.pem');
    
    if (fs.existsSync(keyPath) && fs.existsSync(certPath)) {
      console.log('🏠 Using localhost certificates (localhost only)');
      return {
        key: fs.readFileSync(keyPath),
        cert: fs.readFileSync(certPath)
      };
    }
  } catch (error) {
    console.warn('Could not load HTTPS certificates:', error.message);
  }
  return null;
};

// Workaround for Next.js 15.3.3 Watchpack issue
const app = next({ 
  dev, 
  hostname, 
  port,
  // Add custom webpack config to avoid Watchpack issues
  webpack: (config, { isServer }) => {
    if (!isServer) {
      config.watchOptions = {
        poll: 1000,
        aggregateTimeout: 300,
      };
    }
    return config;
  }
});
const handle = app.getRequestHandler();

function initializeWebSocketServer(server) {
  // Prevent multiple instances of the WebSocket server in development
  if (global.wsServer) {
    console.log('WebSocket server is already running.');
    return;
  }

  const wss = new WebSocket.Server({ noServer: true });
  global.wsServer = wss;

  const clients = global.wsClients;
  const roomSubscriptions = global.wsRoomSubscriptions;
  const userPresence = global.wsUserPresence;
  
  // Make clients accessible globally for debug endpoints
  global.wsClients = clients;

  /**
   * Broadcasts the updated list of participants to everyone in a room.
   * @param {string} roomId The ID of the room.
   */
  const broadcastParticipantList = (roomId) => {
    const participants = [];
    if (roomSubscriptions.has(roomId)) {
      for (const ws of roomSubscriptions.get(roomId)) {
        const clientInfo = clients.get(ws);
        if (clientInfo) {
          // Avoid adding duplicates if a user has multiple connections
          if (!participants.some(p => p.id === clientInfo.user.id)) {
            participants.push(clientInfo.user);
          }
        }
      }
    }
    
    const message = JSON.stringify({
      type: 'PARTICIPANT_LIST_UPDATE',
      data: { roomId, participants },
    });

    if (roomSubscriptions.has(roomId)) {
      roomSubscriptions.get(roomId).forEach(ws => ws.send(message));
    }
  };

  // Handle server upgrade requests to switch to WebSocket protocol
  server.on('upgrade', (request, socket, head) => {
    const { pathname } = parse(request.url, true);
    const clientIP = request.socket.remoteAddress;
    const origin = request.headers.origin;
    
    console.log(`[${new Date().toISOString()}] 🔌 WebSocket upgrade request from ${clientIP} to ${pathname}`);
    
    if (pathname === '/_ws') {
      // SECURITY: Origin validation to prevent Cross-Site WebSocket Hijacking (CSWH)
      const allowedOrigins = [
        'https://localhost:3443',
        'https://127.0.0.1:3443',
        'https://10.119.255.188:3443',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://10.119.255.188:3000'
      ];
      
      if (origin && !allowedOrigins.includes(origin)) {
        console.log(`[${new Date().toISOString()}] 🚨 SECURITY: Rejecting WebSocket from unauthorized origin: ${origin}`);
        socket.destroy();
        return;
      }
      
      console.log(`[${new Date().toISOString()}] ✅ Accepting WebSocket upgrade for /_ws from ${clientIP} (Origin: ${origin || 'none'})`);
      wss.handleUpgrade(request, socket, head, (ws) => {
        wss.emit('connection', ws, request);
      });
    } else if (pathname === '/_next/webpack-hmr') {
      console.log(`[${new Date().toISOString()}] ✅ Allowing Next.js HMR WebSocket from ${clientIP}`);
      // Allow Next.js HMR WebSocket connections
      return;
    } else {
      console.log(`[${new Date().toISOString()}] ❌ Rejecting WebSocket upgrade for unknown path: ${pathname} from ${clientIP}`);
      socket.destroy();
    }
  });

  wss.on('connection', (ws, req) => {
    const url = new URL(req.url, `http://${req.headers.host}`);
    
    // SECURITY: Support both new token format and legacy format during transition
    const token = url.searchParams.get('token');
    const legacyUserId = url.searchParams.get('userId');
    const legacyUser = url.searchParams.get('user');
    const legacyConnId = url.searchParams.get('connId');
    
    let userId, user, connId;
    
    if (token) {
      // NEW SECURE TOKEN FORMAT
      try {
        const decoded = JSON.parse(Buffer.from(token, 'base64').toString());
        userId = decoded.userId;
        connId = decoded.connId || 'unknown';
        user = {
          id: decoded.userId,
          name: decoded.name,
        };
        
        // SECURITY: Token age validation (prevent replay attacks)
        const tokenAge = Date.now() - decoded.timestamp;
        if (tokenAge > 300000) { // 5 minutes max token age
          console.log(`[${new Date().toISOString()}] 🚨 SECURITY: Token expired (age: ${tokenAge}ms)`);
          ws.close(1008, 'Token expired');
          return;
        }
        
        console.log(`[${new Date().toISOString()}] ✅ Secure token authentication for user: ${user.name} (${userId}) [${connId}]`);
        
      } catch (error) {
        console.log(`[${new Date().toISOString()}] 🚨 SECURITY: Invalid token format:`, error.message);
        ws.close(1008, 'Invalid token format');
        return;
      }
    } else if (legacyUserId && legacyUser) {
      // LEGACY FORMAT (temporarily supported for transition)
      try {
        userId = legacyUserId;
        user = JSON.parse(decodeURIComponent(legacyUser));
        connId = legacyConnId || 'unknown';
        
        console.log(`[${new Date().toISOString()}] ⚠️ LEGACY: Using legacy authentication for user: ${user.name} (${userId}) [${connId}]`);
        
      } catch (error) {
        console.log(`[${new Date().toISOString()}] 🚨 SECURITY: Invalid legacy user format:`, error.message);
        ws.close(1008, 'Invalid user data');
        return;
      }
    } else {
      console.log(`[${new Date().toISOString()}] 🚨 SECURITY: No authentication provided`);
      ws.close(1008, 'Authentication required');
      return;
    }

    if (!userId || !user.id) {
      console.log(`[${new Date().toISOString()}] 🚨 SECURITY: Connection rejected - invalid user data in token`);
      ws.close(1008, 'Invalid user data');
      return;
    }
    
    console.log(`[${new Date().toISOString()}] ✅ WebSocket connection established for user: ${user.name} (${userId}) [${connId}]`);

    // SECURITY: Rate limiting per connection
    const rateLimiter = {
      messages: 0,
      lastReset: Date.now(),
      maxMessages: 100, // Max 100 messages per minute per connection
      windowMs: 60000
    };

    // Set user presence to online
    userPresence.set(userId, {
      status: 'online',
      lastSeen: Date.now(),
      currentRoom: null
    });

    const clientInfo = { userId, user, rooms: new Set(), isAlive: true, connId, rateLimiter };
    clients.set(ws, clientInfo);
    // Also store client info on the WebSocket instance for API access
    ws.clientInfo = clientInfo;

    // Broadcast presence update
    const presenceMessage = JSON.stringify({
      type: 'PRESENCE_UPDATE',
      data: {
        userId,
        status: 'online',
        timestamp: Date.now()
      }
    });
    
    // Broadcast to all connected clients
    wss.clients.forEach(client => {
      if (client.readyState === WebSocket.OPEN) {
        try {
          client.send(presenceMessage);
        } catch (error) {
          console.error(`[${new Date().toISOString()}] Error broadcasting presence message:`, error);
        }
      }
    });

    ws.on('message', (data) => {
      const clientInfo = clients.get(ws);
      if (!clientInfo) return;

      // SECURITY: Rate limiting check
      const now = Date.now();
      if (now - clientInfo.rateLimiter.lastReset > clientInfo.rateLimiter.windowMs) {
        clientInfo.rateLimiter.messages = 0;
        clientInfo.rateLimiter.lastReset = now;
      }
      
      if (clientInfo.rateLimiter.messages >= clientInfo.rateLimiter.maxMessages) {
        console.log(`[${new Date().toISOString()}] 🚨 SECURITY: Rate limit exceeded for user ${userId}, closing connection`);
        ws.close(1008, 'Rate limit exceeded');
        return;
      }
      clientInfo.rateLimiter.messages++;

      // Heartbeat check
      if (data.toString() === '{"type":"ping"}') {
        clientInfo.isAlive = true;
        ws.send(JSON.stringify({ type: 'pong' }));
        return;
      }

      try {
        const message = JSON.parse(data);
        
        // SECURITY: Input validation
        if (!message || typeof message !== 'object' || !message.type) {
          console.log(`[${new Date().toISOString()}] 🚨 SECURITY: Invalid message format from user ${userId}`);
          return;
        }

        // SECURITY: Message type validation (whitelist)
        const allowedTypes = [
          'JOIN_ROOM', 'LEAVE_ROOM', 'CHAT_MESSAGE', 'DIRECT_MESSAGE', 'TYPING_START', 'TYPING_STOP',
                  'webrtc-offer', 'webrtc-answer', 'webrtc-ice-candidate', 'webrtc-end-call',
        'webrtc-call-rejected', 'webrtc-call-accepted', 'webrtc-call-cancelled', 'ping', 'pong'
        ];
        
        if (!allowedTypes.includes(message.type)) {
          console.log(`[${new Date().toISOString()}] 🚨 SECURITY: Unauthorized message type '${message.type}' from user ${userId}`);
          return;
        }

        switch (message.type) {
          case 'JOIN_ROOM': {
            const { chatRoomId } = message.data;
            if (chatRoomId) {
              if (!roomSubscriptions.has(chatRoomId)) {
                roomSubscriptions.set(chatRoomId, new Set());
              }
              roomSubscriptions.get(chatRoomId).add(ws);
              clientInfo.rooms.add(chatRoomId);
              
              // Update user presence with current room
              if (userPresence.has(clientInfo.userId)) {
                userPresence.set(clientInfo.userId, {
                  ...userPresence.get(clientInfo.userId),
                  currentRoom: chatRoomId
                });
              }
              
              console.log(`[${new Date().toISOString()}] User ${clientInfo.user.name} joined room ${chatRoomId}`);
              broadcastParticipantList(chatRoomId);
              
              // Send acknowledgment if required
              if (message.requiresAck) {
                ws.send(JSON.stringify({
                  type: 'MESSAGE_ACK',
                  data: { messageId: message.id, success: true }
                }));
              }
            }
            break;
          }

          case 'LEAVE_ROOM': {
            const { chatRoomId } = message.data;
            if (chatRoomId && roomSubscriptions.has(chatRoomId)) {
              roomSubscriptions.get(chatRoomId).delete(ws);
              clientInfo.rooms.delete(chatRoomId);
              
              // Update user presence
              if (userPresence.has(clientInfo.userId)) {
                const presence = userPresence.get(clientInfo.userId);
                if (presence.currentRoom === chatRoomId) {
                  userPresence.set(clientInfo.userId, {
                    ...presence,
                    currentRoom: null
                  });
                }
              }
              
              console.log(`[${new Date().toISOString()}] User ${clientInfo.user.name} left room ${chatRoomId}`);
              broadcastParticipantList(chatRoomId);
              
              // Send acknowledgment if required
              if (message.requiresAck) {
                ws.send(JSON.stringify({
                  type: 'MESSAGE_ACK',
                  data: { messageId: message.id, success: true }
                }));
              }
            }
            break;
          }

          case 'PRESENCE_UPDATE': {
            const { status } = message.data;
            if (userPresence.has(clientInfo.userId)) {
              userPresence.set(clientInfo.userId, {
                ...userPresence.get(clientInfo.userId),
                status,
                lastSeen: Date.now()
              });
              
              // Broadcast presence update to all clients
              const presenceMessage = JSON.stringify({
                type: 'PRESENCE_UPDATE',
                data: {
                  userId: clientInfo.userId,
                  status,
                  timestamp: Date.now()
                }
              });
              
              wss.clients.forEach(client => {
                if (client.readyState === WebSocket.OPEN) {
                  client.send(presenceMessage);
                }
              });
            }
            break;
          }

          case 'DIRECT_MESSAGE': {
            // Handle direct messages between users
            const { recipientId } = message.data;
            if (recipientId) {
              const outboundMessage = JSON.stringify(message);
              
              // Find the recipient's WebSocket connection(s)
              clients.forEach((clientInfo, clientWs) => {
                if (clientInfo.userId === recipientId && clientWs.readyState === WebSocket.OPEN) {
                  clientWs.send(outboundMessage);
                }
              });
              
              console.log(`[${new Date().toISOString()}] Direct message sent from ${clientInfo.user.name} to user ${recipientId}`);
            }
            break;
          }

          case 'CHAT_MESSAGE': {
            const { chatRoomId } = message.data;
            if (chatRoomId && roomSubscriptions.has(chatRoomId)) {
              const outboundMessage = JSON.stringify(message);
              roomSubscriptions.get(chatRoomId).forEach(clientWs => {
                if (clientWs.readyState === WebSocket.OPEN) {
                  clientWs.send(outboundMessage);
                }
              });
              
              // Send acknowledgment if required
              if (message.requiresAck) {
                ws.send(JSON.stringify({
                  type: 'MESSAGE_ACK',
                  data: { messageId: message.id, success: true }
                }));
              }
            }
            break;
          }

          case 'TYPING': {
            const { roomId } = message.data;
            if (roomId && roomSubscriptions.has(roomId)) {
              const outboundMessage = JSON.stringify(message);
              roomSubscriptions.get(roomId).forEach(clientWs => {
                if (clientWs !== ws && clientWs.readyState === WebSocket.OPEN) {
                  clientWs.send(outboundMessage);
                }
              });
            }
            break;
          }

          case 'ping': {
            // Enhanced ping with latency measurement
            ws.send(JSON.stringify({ 
              type: 'pong',
              timestamp: Date.now()
            }));
            // Mark client as alive when they send ping
            if (clientInfo) {
              clientInfo.isAlive = true;
            }
            break;
          }

          case 'pong': {
            // Client responded to our ping - mark as alive
            if (clientInfo) {
              clientInfo.isAlive = true;
              console.log(`[${new Date().toISOString()}] Received pong from ${clientInfo.user.name} - connection healthy`);
            }
            break;
          }

          // WebRTC Signaling for Video Calls
          case 'webrtc-offer':
          case 'webrtc-answer':
          case 'webrtc-ice-candidate':
          case 'webrtc-end-call':
          case 'webrtc-call-rejected':
          case 'webrtc-call-accepted':
          case 'webrtc-call-cancelled': {
            const { recipientId } = message.data;
            if (recipientId) {
              const signalMessage = JSON.stringify({
                type: message.type,
                senderId: clientInfo.userId,
                senderName: clientInfo.user.name || 'Unknown User',
                data: message.data
              });

              // DEBUG: Log all connected user IDs to help identify the issue
              console.log(`🔍 DEBUG - WebRTC signaling attempt:`);
              console.log(`🔍 Sender: ${clientInfo.user.name} (${clientInfo.userId})`);
              console.log(`🔍 Looking for recipient: ${recipientId}`);
              console.log(`🔍 Currently connected users:`);
              
              const connectedUserIds = [];
              clients.forEach((info, ws) => {
                const status = ws.readyState === WebSocket.OPEN ? 'OPEN' : 'CLOSED';
                console.log(`🔍   - ${info.user.name} (${info.userId}) [${status}]`);
                connectedUserIds.push(info.userId);
              });
              
              console.log(`🔍 Total connected users: ${connectedUserIds.length}`);

              // Find the recipient's WebSocket connection
              let signalSent = false;
              clients.forEach((recipientInfo, clientWs) => {
                if (recipientInfo.userId === recipientId && clientWs.readyState === WebSocket.OPEN) {
                  try {
                    clientWs.send(signalMessage);
                    signalSent = true;
                    console.log(`✅ Sending WebRTC signal to ${recipientInfo.user.name} (${recipientInfo.userId})`);
                  } catch (error) {
                    console.error(`[${new Date().toISOString()}] Error sending WebRTC signal:`, error);
                  }
                }
              });

              if (!signalSent) {
                console.log(`❌ Recipient ${recipientId} not found in connected users`);
                console.log(`💡 Possible causes:`);
                console.log(`💡   1. User disconnected/logged out`);
                console.log(`💡   2. Wrong user ID being used`);
                console.log(`💡   3. User connected to different server instance`);
                console.log(`💡   4. Browser tab closed or refreshed`);
                
                // Send error back to caller
                ws.send(JSON.stringify({
                  type: 'webrtc-error',
                  error: 'RECIPIENT_OFFLINE',
                  message: 'The user you are trying to call is not currently online',
                  recipientId: recipientId
                }));
                
                console.log(`WebRTC ${message.type} from ${clientInfo.user.name} to ${recipientId}: recipient offline`);
              }
            }
            break;
          }
        }
      } catch (error) {
        console.error('Failed to process message:', data.toString(), error);
      }
    });

    ws.on('close', (code, reason) => {
      const clientInfo = clients.get(ws);
      if (clientInfo) {
        console.log(`[${new Date().toISOString()}] WebSocket connection closed for user ${clientInfo.user.name} [${clientInfo.connId || 'unknown'}] - Code: ${code}, Reason: ${reason || 'No reason provided'}`);
        
        // Update user presence to offline
        if (userPresence.has(clientInfo.userId)) {
          userPresence.set(clientInfo.userId, {
            ...userPresence.get(clientInfo.userId),
            status: 'offline',
            lastSeen: Date.now(),
            currentRoom: null
          });
          
          // Broadcast presence update
          const presenceMessage = JSON.stringify({
            type: 'PRESENCE_UPDATE',
            data: {
              userId: clientInfo.userId,
              status: 'offline',
              timestamp: Date.now()
            }
          });
          
          wss.clients.forEach(client => {
            if (client.readyState === WebSocket.OPEN) {
              client.send(presenceMessage);
            }
          });
        }
        
        clientInfo.rooms.forEach(roomId => {
          if (roomSubscriptions.has(roomId)) {
            roomSubscriptions.get(roomId).delete(ws);
            if (roomSubscriptions.get(roomId).size === 0) {
              roomSubscriptions.delete(roomId);
              console.log(`[${new Date().toISOString()}] Room ${roomId} is now empty and removed.`);
            } else {
              broadcastParticipantList(roomId);
            }
          }
        });
        clients.delete(ws);
        // Clean up client info from WebSocket instance
        delete ws.clientInfo;
      }
    });

    ws.on('error', (error) => {
      const clientInfo = clients.get(ws);
      console.error(`[${new Date().toISOString()}] WebSocket error for user ${clientInfo ? clientInfo.user.name : 'unknown'}:`, error);
    });
  });
  
  // Set up a heartbeat to clean up disconnected clients.
  const interval = setInterval(() => {
    wss.clients.forEach((ws) => {
      const clientInfo = clients.get(ws);
      if (clientInfo) {
        if (!clientInfo.isAlive) {
          console.log(`[${new Date().toISOString()}] Terminating unresponsive connection for user ${clientInfo.user.name}`);
          return ws.terminate();
        }
        clientInfo.isAlive = false;
        // Send ping to check if client is responsive
        try {
          ws.send(JSON.stringify({ type: 'ping', timestamp: Date.now() }));
        } catch (error) {
          console.error(`[${new Date().toISOString()}] Error sending ping to ${clientInfo.user.name}:`, error);
          ws.terminate();
        }
      }
    });
    
    // Memory cleanup: Force garbage collection if memory usage is high
    const memUsage = process.memoryUsage();
    if (memUsage.heapUsed > 1024 * 1024 * 1024) { // 1GB threshold
      console.log(`[${new Date().toISOString()}] 🧹 High memory usage detected: ${Math.round(memUsage.heapUsed / 1024 / 1024)}MB, forcing cleanup`);
      if (global.gc) {
        global.gc();
      }
    }
  }, 120000); // Increased from 60s to 120s (2 minutes) to be more forgiving for idle users

  wss.on('close', () => {
    clearInterval(interval);
  });

  console.log('WebSocket server initialized and attached to the HTTP server.');
}

app.prepare().then(() => {
  console.log('📦 Next.js app prepared successfully');
  const httpsOptions = getHttpsOptions();
  console.log('🔐 HTTPS options:', httpsOptions ? 'Found certificates' : 'No certificates');
  
  // Create HTTP server (redirects to HTTPS if certificates available)
  const httpServer = createHttpServer((req, res) => {
    try {
      // Debug endpoint to check connected WebSocket users
      if (req.url === '/debug/websocket-users') {
        const connectedUsers = [];
        if (typeof clients !== 'undefined') {
          clients.forEach((info, ws) => {
            connectedUsers.push({
              userId: info.userId,
              userName: info.user.name,
              userEmail: info.user.email,
              connectionId: info.connId,
              isAlive: info.isAlive,
              wsState: ws.readyState,
              wsStateText: ws.readyState === 0 ? 'CONNECTING' : 
                          ws.readyState === 1 ? 'OPEN' : 
                          ws.readyState === 2 ? 'CLOSING' : 'CLOSED',
              rooms: Array.from(info.rooms || [])
            });
          });
        }
        
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
          timestamp: new Date().toISOString(),
          totalConnectedUsers: connectedUsers.length,
          users: connectedUsers
        }, null, 2));
        return;
      }
      
      // In development with HTTPS available, redirect to HTTPS
      if (httpsOptions) {
        const host = req.headers.host?.replace(`:${port}`, `:${httpsPort}`);
        res.writeHead(301, {
          Location: `https://${host}${req.url}`
        });
        res.end();
        return;
      }
      
      const parsedUrl = parse(req.url, true);
      handle(req, res, parsedUrl);
    } catch (err) {
      console.error('Error handling request:', err);
      res.statusCode = 500;
      res.end('Internal Server Error');
    }
  });

  httpServer.listen(port, '0.0.0.0', (err) => {
    if (err) throw err;
    console.log(`> HTTP server ready on http://0.0.0.0:${port}`);
    console.log(`> 🌐 Server accessible on network at http://${getNetworkIP()}:${port}`);
    
    if (httpsOptions) {
      console.log(`> ⚠️  HTTP redirects to HTTPS for video calling support`);
    } else {
      console.log('> ⚠️  HTTPS certificates not found. Video calling may not work.');
      console.log('> Run "npm run generate-certificates" to create HTTPS certificates.');
    }
  });

  httpServer.on('error', (err) => {
    console.error('HTTP Server error:', err);
  });

  // Start HTTPS server if certificates are available
  if (httpsOptions) {
    const httpsServer = createServer(httpsOptions, (req, res) => {
      try {
        // Debug endpoint to check connected WebSocket users
        if (req.url === '/debug/websocket-users') {
          const connectedUsers = [];
          if (typeof clients !== 'undefined') {
            clients.forEach((info, ws) => {
              connectedUsers.push({
                userId: info.userId,
                userName: info.user.name,
                userEmail: info.user.email,
                connectionId: info.connId,
                isAlive: info.isAlive,
                wsState: ws.readyState,
                wsStateText: ws.readyState === 0 ? 'CONNECTING' : 
                            ws.readyState === 1 ? 'OPEN' : 
                            ws.readyState === 2 ? 'CLOSING' : 'CLOSED',
                rooms: Array.from(info.rooms || [])
              });
            });
          }
          
          res.writeHead(200, { 'Content-Type': 'application/json' });
          res.end(JSON.stringify({
            timestamp: new Date().toISOString(),
            totalConnectedUsers: connectedUsers.length,
            users: connectedUsers
          }, null, 2));
          return;
        }
        
        const parsedUrl = parse(req.url, true);
        handle(req, res, parsedUrl);
      } catch (err) {
        console.error('Error handling HTTPS request:', err);
        res.statusCode = 500;
        res.end('Internal Server Error');
      }
    });

    httpsServer.listen(httpsPort, '0.0.0.0', (err) => {
      if (err) {
        console.error('Error starting HTTPS server:', err);
        return;
      }
      
      console.log(`> ✅ HTTPS server ready on https://0.0.0.0:${httpsPort}`);
      console.log(`> 🌐 Server accessible on network at https://${getNetworkIP()}:${httpsPort}`);
      console.log(`> 🎥 Video calling enabled with secure connection!`);
      console.log(`> 🔌 WebSocket endpoint: wss://0.0.0.0:${httpsPort}/_ws`);
    });

    httpsServer.on('error', (err) => {
      console.error('HTTPS Server error:', err);
    });

    // Initialize WebSocket server on HTTPS server
    initializeWebSocketServer(httpsServer);
  } else {
    // Initialize WebSocket server on HTTP server if no HTTPS
    console.log('> ⚠️  Running without HTTPS - video calling will not work');
    initializeWebSocketServer(httpServer);
  }

  // Note: API endpoints moved to Next.js API routes
  // /api/user/online-status/[userId].js and /api/users/online.js
});