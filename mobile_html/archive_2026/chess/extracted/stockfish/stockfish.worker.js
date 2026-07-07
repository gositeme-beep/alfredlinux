// Stockfish Web Worker
let engine = null;
let isInitialized = false;
let currentAnalysis = null;

// Handle messages from the main thread
self.onmessage = function(event) {
  const { type, fen } = event.data;

  switch (type) {
    case 'init':
      try {
        console.log('Initializing Stockfish engine...');
        // Initialize Stockfish
        engine = new Worker('/stockfish/stockfish.js');
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
        
        // Wait for engine to be ready
        const readyHandler = (event) => {
          if (event.data === 'ready') {
            engine.removeEventListener('message', readyHandler);
            isInitialized = true;
            self.postMessage('ready');
          }
        };
        engine.addEventListener('message', readyHandler);
      } catch (error) {
        console.error('Error initializing Stockfish:', error);
        self.postMessage({ type: 'error', error: error.message || 'Unknown error' });
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
        engine.postMessage({ type: 'position', fen });
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