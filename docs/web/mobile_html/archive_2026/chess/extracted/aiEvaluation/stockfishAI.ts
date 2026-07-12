import { BoardPosition, PieceColor } from '../../types';
import { StockfishEngine } from './StockfishEngine';

// Initialize Stockfish engine
const engine = new StockfishEngine();

// Helper function to convert board state to FEN
function convertBoardToFEN(boardState: BoardPosition[] | string): string {
  // If already a FEN string, return it
  if (typeof boardState === 'string') {
    return boardState;
  }

  let fen = '';
  let emptyCount = 0;

  // Convert board to FEN notation
  for (let row = 0; row < 8; row++) {
    for (let col = 0; col < 8; col++) {
      const piece = boardState.find(p => p.row === row && p.col === col)?.piece;
      if (!piece) {
        emptyCount++;
      } else {
        if (emptyCount > 0) {
          fen += emptyCount;
          emptyCount = 0;
        }
        // Convert piece type to FEN notation
        const pieceChar = piece.type === 'p' ? 'p' :
                         piece.type === 'n' ? 'n' :
                         piece.type === 'b' ? 'b' :
                         piece.type === 'r' ? 'r' :
                         piece.type === 'q' ? 'q' :
                         piece.type === 'k' ? 'k' : '';
        fen += piece.color === 'w' ? pieceChar.toUpperCase() : pieceChar;
      }
    }
    if (emptyCount > 0) {
      fen += emptyCount;
      emptyCount = 0;
    }
    if (row < 7) fen += '/';
  }

  // Add active color (determine from board state)
  const isWhiteTurn = boardState.some(p => p.piece?.color === 'w' && p.piece?.type === 'k');
  fen += isWhiteTurn ? ' w' : ' b';

  // Add castling availability (simplified)
  fen += ' KQkq';

  // Add en passant target square (none)
  fen += ' -';

  // Add halfmove clock and fullmove number (simplified)
  fen += ' 0 1';

  return fen;
}

export async function getStockfishMove(boardState: BoardPosition[] | string): Promise<{ from: string, to: string }> {
  try {
    const fen = convertBoardToFEN(boardState);
    console.log('Getting Stockfish move for FEN:', fen);
    return await engine.getBestMove(fen);
  } catch (error) {
    console.error('Error in getStockfishMove:', error);
    throw error;
  }
}

export function terminateStockfish(): void {
  engine.terminate();
} 