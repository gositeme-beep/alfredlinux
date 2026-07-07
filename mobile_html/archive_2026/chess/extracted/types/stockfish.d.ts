declare module 'stockfish' {
  export class Stockfish {
    constructor();
    postMessage(message: string): void;
    onmessage: ((event: { data: string }) => void) | null;
    terminate(): void;
  }
} 