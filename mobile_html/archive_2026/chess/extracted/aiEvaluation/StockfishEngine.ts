import { BoardPosition } from '../../types';

export class StockfishEngine {
  private engine: Worker;
  private isReady: boolean = false;
  private currentAnalysis: {
    score: number;
    moves: string[];
  } | null = null;
  private messageHandler: ((event: MessageEvent) => void) | null = null;

  constructor() {
    // Initialize Stockfish worker
    this.engine = new Worker('/stockfish.min.js');
    this.initializeEngine();
  }

  private initializeEngine(): void {
    console.log('Initializing Stockfish engine...');
    
    // Set up message handler
    this.engine.onmessage = (event) => {
      const message = event.data;
      console.log('Stockfish response:', message);

      if (message === 'ready') {
        console.log('Stockfish engine initialized successfully');
        this.isReady = true;
      }
      else if (message.startsWith('info')) {
        // Parse analysis information
        const parts = message.split(' ');
        const scoreIndex = parts.indexOf('score');
        const pvIndex = parts.indexOf('pv');
        
        if (scoreIndex !== -1 && pvIndex !== -1) {
          const scoreType = parts[scoreIndex + 1];
          const scoreValue = parseInt(parts[scoreIndex + 2]);
          const moves = parts.slice(pvIndex + 1);
          
          this.currentAnalysis = {
            score: scoreType === 'cp' ? scoreValue : scoreValue * 100,
            moves
          };
        }
      }
    };

    // Configure engine for maximum strength
    this.engine.postMessage('uci');
    this.engine.postMessage('setoption name Skill Level value 20');
    this.engine.postMessage('setoption name Contempt value 0');
    this.engine.postMessage('setoption name Threads value 4');
    this.engine.postMessage('setoption name Hash value 1024');
    this.engine.postMessage('isready');
  }

  public async getBestMove(fen: string): Promise<{ from: string, to: string }> {
    if (!this.isReady) {
      throw new Error('Stockfish engine not ready');
    }

    return new Promise((resolve, reject) => {
      console.log('Analyzing position with FEN:', fen);
      
      // Reset current analysis
      this.currentAnalysis = null;
      
      // Set up position and start analysis
      this.engine.postMessage(`position fen ${fen}`);
      this.engine.postMessage('go depth 25 movetime 10000');
      
      // Set up timeout
      const timeout = setTimeout(() => {
        if (this.messageHandler) {
          this.engine.onmessage = null;
          this.messageHandler = null;
        }
        reject(new Error('Stockfish move timeout'));
      }, 15000);
      
      // Set up message handler for this move request
      this.messageHandler = (event: MessageEvent) => {
        const message = event.data;
        
        if (message.startsWith('bestmove')) {
          const move = message.split(' ')[1];
          if (move && move !== '(none)') {
            clearTimeout(timeout);
            this.engine.onmessage = null;
            this.messageHandler = null;
            
            // Extract the move in algebraic notation (e.g., "e2e4")
            const from = move.substring(0, 2);
            const to = move.substring(2, 4);
            
            console.log('Stockfish best move:', { from, to });
            resolve({ from, to });
          }
        }
      };
      
      this.engine.onmessage = this.messageHandler;
    });
  }

  public terminate(): void {
    console.log('Terminating Stockfish engine');
    this.engine.postMessage('quit');
    this.isReady = false;
  }
} 