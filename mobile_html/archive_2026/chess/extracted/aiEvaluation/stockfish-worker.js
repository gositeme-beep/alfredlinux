// Stockfish Web Worker
import { Stockfish } from 'stockfish';

let engine = null;
let isInitialized = false;

// Handle messages from the main thread
self.onmessage = function(event) {
  const { type, fen } = event.data;

  switch (type) {
    case 'init':
      try {
        // Initialize Stockfish
        engine = new Stockfish();
        engine.onmessage = function(event) {
          self.postMessage(event.data);
        };
        engine.postMessage('uci');
        isInitialized = true;
        self.postMessage('ready');
      } catch (error) {
        self.postMessage({ type: 'error', error: error.message });
      }
      break;

    case 'position':
      if (!isInitialized || !engine) {
        self.postMessage({ type: 'error', error: 'Engine not initialized' });
        return;
      }

      try {
        engine.postMessage('ucinewgame');
        engine.postMessage(`position fen ${fen}`);
        engine.postMessage('go movetime 5000'); // 5 seconds per move
      } catch (error) {
        self.postMessage({ type: 'error', error: error.message });
      }
      break;

    case 'terminate':
      if (engine) {
        engine.terminate();
        engine = null;
        isInitialized = false;
      }
      break;

    default:
      self.postMessage({ type: 'error', error: 'Unknown command' });
  }
}; 