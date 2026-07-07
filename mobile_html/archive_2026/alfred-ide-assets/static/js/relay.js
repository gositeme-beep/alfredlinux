/**
 * Alfred IDE — voice-relay bridge for the web workbench (loaded from /alfred-ide/static/js/relay.js).
 * Talks to /middleware/api/voice-relay (text-only IDE mode → alfred-chat.php).
 * Uses alfred_ide_token cookie as Bearer + X-Alfred-IDE-Token (middleware accepts hex IDE tokens).
 */
(function (global) {
  'use strict';

  var O = global.location && global.location.origin ? global.location.origin : 'https://gositeme.com';
  var RELAY = O + '/middleware/api/voice-relay';
  var sessionId = null;
  var connected = false;
  var pollActive = false;
  var listeners = [];

  function authHeaders() {
    var h = { 'Content-Type': 'application/json' };
    try {
      var match = global.document && global.document.cookie.match(/(?:^|;\s*)alfred_ide_token=([^;]+)/);
      if (match) {
        var t = decodeURIComponent(match[1]);
        if (t) {
          h['Authorization'] = 'Bearer ' + t;
          h['X-Alfred-IDE-Token'] = t;
        }
      }
    } catch (e) {}
    return h;
  }

  function emit(msg) {
    listeners.forEach(function (fn) {
      try {
        fn(msg);
      } catch (e) {}
    });
  }

  function connect(cb) {
    if (connected && sessionId) {
      if (cb) {
        cb(null);
      }
      return;
    }
    global
      .fetch(RELAY + '/connect', {
        method: 'POST',
        credentials: 'include',
        headers: authHeaders(),
        body: JSON.stringify({
          voice_server: 'wss://127.0.0.1:3006/',
          text_only: true,
          ide_chat: true,
        }),
      })
      .then(function (r) {
        return r.json();
      })
      .then(function (d) {
        if (d && d.ok && d.session_id) {
          sessionId = d.session_id;
          connected = true;
          pollActive = true;
          pollLoop();
          if (cb) {
            cb(null, d);
          }
        } else if (cb) {
          cb((d && d.error) || 'connect_failed');
        }
      })
      .catch(function (e) {
        if (cb) {
          cb(e.message || 'network_error');
        }
      });
  }

  function pollLoop() {
    if (!pollActive || !sessionId) {
      return;
    }
    global
      .fetch(RELAY + '/poll?session_id=' + encodeURIComponent(sessionId), {
        credentials: 'include',
        headers: authHeaders(),
      })
      .then(function (r) {
        return r.json();
      })
      .then(function (d) {
        if (!d || !d.ok) {
          global.setTimeout(pollLoop, 2000);
          return;
        }
        if (d.messages && d.messages.length) {
          d.messages.forEach(function (m) {
            try {
              var msg = typeof m === 'string' ? JSON.parse(m) : m;
              emit(msg);
            } catch (e) {}
          });
        }
        if (pollActive) {
          pollLoop();
        }
      })
      .catch(function () {
        if (pollActive) {
          global.setTimeout(pollLoop, 2000);
        }
      });
  }

  function disconnect() {
    pollActive = false;
    connected = false;
    sessionId = null;
  }

  var api = {
    connect: connect,
    disconnect: disconnect,
    getSessionId: function () {
      return sessionId;
    },
    get baseUrl() {
      return RELAY;
    },
    onMessage: function (fn) {
      listeners.push(fn);
    },
    sendText: function (text, agent, cb) {
      var payload = { type: 'text', text: String(text || '') };
      if (agent) {
        payload.agent = agent;
      }
      function doSend() {
        global
          .fetch(RELAY + '/send', {
            method: 'POST',
            credentials: 'include',
            headers: authHeaders(),
            body: JSON.stringify({ session_id: sessionId, message: payload }),
          })
          .then(function (r) {
            return r.json();
          })
          .then(function (d) {
            if (cb) {
              cb(d && d.ok === false ? d.error || 'send_failed' : null);
            }
          })
          .catch(function (e) {
            if (cb) {
              cb(e.message);
            }
          });
      }
      if (!sessionId || !connected) {
        connect(function (err) {
          if (err) {
            if (cb) {
              cb(err);
            }
            return;
          }
          doSend();
        });
        return;
      }
      doSend();
    },
    send: function (message, cb) {
      if (!sessionId || !connected) {
        connect(function (err) {
          if (err) {
            if (cb) {
              cb(err);
            }
            return;
          }
          api.send(message, cb);
        });
        return;
      }
      global
        .fetch(RELAY + '/send', {
          method: 'POST',
          credentials: 'include',
          headers: authHeaders(),
          body: JSON.stringify({ session_id: sessionId, message: message }),
        })
        .then(function (r) {
          return r.json();
        })
        .then(function (d) {
          if (cb) {
            cb(d && d.ok === false ? d.error || 'send_failed' : null);
          }
        })
        .catch(function (e) {
          if (cb) {
            cb(e.message);
          }
        });
    },
  };

  global.AlfredIDERelay = api;
  global.AlfredRelay = api;
  global.__ALFRED_RELAY_VERSION = '1.0.2-gositeme';
  try {
    global.dispatchEvent(new CustomEvent('alfred-ide-relay-ready', { detail: api }));
  } catch (e) {}
})(typeof window !== 'undefined' ? window : this);
