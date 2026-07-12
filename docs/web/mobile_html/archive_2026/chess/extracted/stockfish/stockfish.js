// Stockfish Chess Engine
importScripts('/stockfish.min.js');

let engine = null;
let isInitialized = false;
let currentAnalysis = null;

// Handle messages from the main thread
self.onmessage = function(event) {
  const { type, fen } = event.data;

  switch (type) {
    case 'init':
      if (!engine) {
        engine = new Stockfish();
        engine.onmessage = function(event) {
          const message = event.data;
          console.log('Stockfish response:', message);
          
          // Handle analysis info
          if (message.startsWith('info')) {
            const parts = message.split(' ');
            const scoreIndex = parts.indexOf('score');
            const pvIndex = parts.indexOf('pv');
            
            if (scoreIndex !== -1 && pvIndex !== -1) {
              const scoreType = parts[scoreIndex + 1];
              const scoreValue = parseInt(parts[scoreIndex + 2]);
              const pvMoves = parts.slice(pvIndex + 1);
              
              currentAnalysis = {
                score: scoreType === 'cp' ? scoreValue : scoreType === 'mate' ? (scoreValue > 0 ? 10000 : -10000) : 0,
                moves: pvMoves
              };
            }
          }
          
          self.postMessage(message);
        };
        
        // Configure Stockfish for maximum strength
        engine.postMessage('uci');
        engine.postMessage('setoption name Skill Level value 20');
        engine.postMessage('setoption name UCI_LimitStrength value false');
        engine.postMessage('setoption name UCI_Elo value 3200');
        engine.postMessage('setoption name Hash value 2048');
        engine.postMessage('setoption name Threads value 4');
        engine.postMessage('setoption name MultiPV value 3');
        engine.postMessage('setoption name Contempt value 0');
        engine.postMessage('setoption name Move Overhead value 0');
        engine.postMessage('setoption name Slow Mover value 100');
        engine.postMessage('setoption name Ponder value true');
        engine.postMessage('isready');
        
        isInitialized = true;
        self.postMessage('ready');
      }
      break;

    case 'position':
      if (!isInitialized || !engine) {
        console.error('Engine not initialized');
        self.postMessage({ type: 'error', error: 'Engine not initialized' });
        return;
      }

      try {
        console.log('Setting up position with FEN:', fen);
        currentAnalysis = null;
        engine.postMessage('ucinewgame');
        engine.postMessage(`position fen ${fen}`);
        engine.postMessage('go movetime 10000 depth 25');
      } catch (error) {
        console.error('Error setting position:', error);
        self.postMessage({ type: 'error', error: error.message || 'Unknown error' });
      }
      break;

    case 'terminate':
      if (engine) {
        console.log('Terminating Stockfish engine');
        engine.terminate();
        engine = null;
        isInitialized = false;
      }
      break;

    default:
      console.error('Unknown command:', type);
      self.postMessage({ type: 'error', error: 'Unknown command' });
  }
};

export class Stockfish {
  constructor() {
    this.onmessage = null;
  }

  postMessage(message) {
    if (this.onmessage) {
      this.onmessage({ data: message });
    }
  }

  terminate() {
    // Cleanup if needed
  }
} 