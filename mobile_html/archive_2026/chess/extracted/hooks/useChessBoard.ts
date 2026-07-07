import { useState, useCallback, useEffect, useRef } from 'react';
import { Chess, Square, Move, Color, Piece } from 'chess.js';
import { 
  BoardPosition, 
  ChessMove, 
  PieceColor, 
  PlayerColorType, 
  PieceType,
  GameStatus,
  BoardState,
  ChessPiece
} from '../types';
import { getStockfishMove, terminateStockfish } from '../utils/aiEvaluation/stockfishAI';

// Enhanced piece values with positional bonuses
const PIECE_VALUES = {
  p: 100,   // Pawn
  n: 320,   // Knight
  b: 330,   // Bishop
  r: 500,   // Rook
  q: 900,   // Queen
  k: 20000  // King
};

// Improved center control evaluation
const CENTER_SQUARES = ['d4', 'd5', 'e4', 'e5'];
const EXTENDED_CENTER = ['c3', 'c4', 'c5', 'c6', 'd3', 'd6', 'e3', 'e6', 'f3', 'f4', 'f5', 'f6'];

// Enhanced pawn structure evaluation
const PAWN_TABLE = [
  [ 0,  0,  0,  0,  0,  0,  0,  0],
  [50, 50, 50, 50, 50, 50, 50, 50],
  [10, 10, 20, 30, 30, 20, 10, 10],
  [ 5,  5, 10, 25, 25, 10,  5,  5],
  [ 0,  0,  0, 20, 20,  0,  0,  0],
  [ 5, -5,-10,  0,  0,-10, -5,  5],
  [ 5, 10, 10,-20,-20, 10, 10,  5],
  [ 0,  0,  0,  0,  0,  0,  0,  0]
];

// Improved knight positioning
const KNIGHT_TABLE = [
  [-50,-40,-30,-30,-30,-30,-40,-50],
  [-40,-20,  0,  0,  0,  0,-20,-40],
  [-30,  0, 10, 15, 15, 10,  0,-30],
  [-30,  5, 15, 20, 20, 15,  5,-30],
  [-30,  0, 15, 20, 20, 15,  0,-30],
  [-30,  5, 10, 15, 15, 10,  5,-30],
  [-40,-20,  0,  5,  5,  0,-20,-40],
  [-50,-40,-30,-30,-30,-30,-40,-50]
];

// Bishop positioning
const BISHOP_TABLE = [
  [-20,-10,-10,-10,-10,-10,-10,-20],
  [-10,  0,  0,  0,  0,  0,  0,-10],
  [-10,  0,  5, 10, 10,  5,  0,-10],
  [-10,  5,  5, 10, 10,  5,  5,-10],
  [-10,  0, 10, 10, 10, 10,  0,-10],
  [-10, 10, 10, 10, 10, 10, 10,-10],
  [-10,  5,  0,  0,  0,  0,  5,-10],
  [-20,-10,-10,-10,-10,-10,-10,-20]
];

// Rook positioning
const ROOK_TABLE = [
  [ 0,  0,  0,  0,  0,  0,  0,  0],
  [ 5, 10, 10, 10, 10, 10, 10,  5],
  [-5,  0,  0,  0,  0,  0,  0, -5],
  [-5,  0,  0,  0,  0,  0,  0, -5],
  [-5,  0,  0,  0,  0,  0,  0, -5],
  [-5,  0,  0,  0,  0,  0,  0, -5],
  [-5,  0,  0,  0,  0,  0,  0, -5],
  [ 0,  0,  0,  5,  5,  0,  0,  0]
];

// Queen positioning
const QUEEN_TABLE = [
  [-20,-10,-10, -5, -5,-10,-10,-20],
  [-10,  0,  0,  0,  0,  0,  0,-10],
  [-10,  0,  5,  5,  5,  5,  0,-10],
  [ -5,  0,  5,  5,  5,  5,  0, -5],
  [  0,  0,  5,  5,  5,  5,  0, -5],
  [-10,  5,  5,  5,  5,  5,  0,-10],
  [-10,  0,  5,  0,  0,  0,  0,-10],
  [-20,-10,-10, -5, -5,-10,-10,-20]
];

// King positioning (middlegame)
const KING_TABLE_MIDDLE = [
  [-30,-40,-40,-50,-50,-40,-40,-30],
  [-30,-40,-40,-50,-50,-40,-40,-30],
  [-30,-40,-40,-50,-50,-40,-40,-30],
  [-30,-40,-40,-50,-50,-40,-40,-30],
  [-20,-30,-30,-40,-40,-30,-30,-20],
  [-10,-20,-20,-20,-20,-20,-20,-10],
  [ 20, 20,  0,  0,  0,  0, 20, 20],
  [ 20, 30, 10,  0,  0, 10, 30, 20]
];

// King positioning (endgame)
const KING_TABLE_END = [
  [-50,-40,-30,-20,-20,-30,-40,-50],
  [-30,-20,-10,  0,  0,-10,-20,-30],
  [-30,-10, 20, 30, 30, 20,-10,-30],
  [-30,-10, 30, 40, 40, 30,-10,-30],
  [-30,-10, 30, 40, 40, 30,-10,-30],
  [-30,-10, 20, 30, 30, 20,-10,-30],
  [-30,-30,  0,  0,  0,  0,-30,-30],
  [-50,-30,-30,-30,-30,-30,-30,-50]
];

// Opening book for common positions
const OPENING_BOOK = new Map([
  ['rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', [
    { from: 'e2', to: 'e4' },  // King's Pawn
    { from: 'd2', to: 'd4' },  // Queen's Pawn
    { from: 'c2', to: 'c4' }   // English Opening
  ]],
  ['rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3', [
    { from: 'e7', to: 'e5' },  // King's Pawn Game
    { from: 'c7', to: 'c5' },  // Sicilian Defense
    { from: 'e7', to: 'e6' }   // French Defense
  ]],
  ['rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq d3', [
    { from: 'd7', to: 'd5' },  // Queen's Pawn Game
    { from: 'nf6', to: 'f6' }, // Indian Defense
    { from: 'f7', to: 'f5' }   // Dutch Defense
  ]]
]);

interface UseChessBoardProps {
  onMoveMade?: (move: ChessMove) => void;
  remoteMove?: ChessMove | null;
  playerColor?: PlayerColorType;
  computerDifficulty?: number;
  analysisMode?: boolean;
  initialFen?: string;
  onMoveHistoryUpdate?: (moves: ChessMove[]) => void;
  onGameOver?: (winner: PieceColor | null) => void;
}

interface TranspositionTableEntry {
  depth: number;
  score: number;
  flag: 'exact' | 'lowerbound' | 'upperbound';
}

interface ChessMoveWithScore extends Move {
  score?: number;
}

interface BoardEvaluation {
  score: number;
  moves: string[];
}

interface ChessMoveWithPosition extends Move {
  from: Square;
  to: Square;
}

export const useChessBoard = ({ 
  onMoveMade,
  remoteMove,
  playerColor,
  computerDifficulty = 3,
  analysisMode,
  initialFen,
  onMoveHistoryUpdate,
  onGameOver
}: UseChessBoardProps) => {
  const [game] = useState(new Chess());
  const [selectedSquare, setSelectedSquare] = useState<Square | null>(null);
  const [lastMove, setLastMove] = useState<ChessMove | null>(null);
  const [moveHistory, setMoveHistory] = useState<string[]>([]);
  const [capturedPieces, setCapturedPieces] = useState<{ white: string[], black: string[] }>({ white: [], black: [] });
  const [transpositionTable] = useState<Map<string, TranspositionTableEntry>>(new Map());

  const getBoardState = useCallback((): BoardState => {
    const board: BoardState = {};
    for (let i = 0; i < 8; i++) {
      for (let j = 0; j < 8; j++) {
        const square = String.fromCharCode(97 + j) + (8 - i) as Square;
        const piece = game.get(square);
        board[square] = piece ? { type: piece.type, color: piece.color } : null;
      }
    }
    return board;
  }, [game]);

  const getGameStatus = useCallback((): GameStatus => {
    if (game.isCheckmate()) return 'checkmate';
    if (game.isCheck()) return 'check';
    if (game.isDraw() || game.isStalemate()) return 'draw';
    return 'active';
  }, [game]);

  const isValidMove = useCallback((from: Square, to: Square): boolean => {
    try {
      const moves = game.moves({ square: from, verbose: true });
      return moves.some(move => move.to === to);
    } catch {
      return false;
    }
  }, [game]);

  const makeMove = useCallback((from: Square, to: Square): boolean => {
    try {
      const moveResult = game.move({ from, to });
      if (moveResult) {
        const fromPosition: BoardPosition = {
          row: parseInt(from[1]),
          col: from.charCodeAt(0) - 97,
          piece: {
            type: moveResult.piece as PieceType,
            color: moveResult.color as PieceColor
          }
        };
        
        const toPosition: BoardPosition = {
          row: parseInt(to[1]),
          col: to.charCodeAt(0) - 97,
          piece: {
            type: moveResult.piece as PieceType,
            color: moveResult.color as PieceColor
          }
        };

        const move: ChessMove = {
          from: fromPosition,
          to: toPosition,
          piece: {
            type: moveResult.piece as PieceType,
            color: moveResult.color as PieceColor
          },
          notation: moveResult.san,
          number: game.moveNumber(),
          color: moveResult.color === 'w' ? 'white' : 'black'
        };
        
        setLastMove(move);
        onMoveMade?.(move);
        return true;
      }
      return false;
    } catch (error: unknown) {
      console.error('Error making move:', error);
      return false;
    }
  }, [game, onMoveMade]);

  const evaluatePosition = (): number => {
    const material = evaluateMaterial();
    const position = evaluatePositionalFactors();
    return material + position;
  };

  const evaluateMaterial = (): number => {
    let score = 0;
    const board = game.board() as (Piece | null)[][];
    
    const pieceValues: Record<PieceType, number> = {
      p: 100,
      n: 320,
      b: 330,
      r: 500,
      q: 900,
      k: 20000
    };

    for (let i = 0; i < 8; i++) {
      for (let j = 0; j < 8; j++) {
        const piece = board[i][j];
        if (piece) {
          const value = pieceValues[piece.type as PieceType];
          score += piece.color === 'w' ? value : -value;
        }
      }
    }
    
    return score;
  };

  const evaluatePositionalFactors = (): number => {
    let score = 0;
    const board = game.board() as (Piece | null)[][];
    
    // Center control
    const centerSquares = ['d4', 'd5', 'e4', 'e5'] as const;
    for (const square of centerSquares) {
      const piece = game.get(square as Square);
      if (piece) {
        score += piece.color === 'w' ? 30 : -30;
      }
    }
    
    // Piece development in opening
    if (game.moveNumber() < 10) {
      const developedPieces = ['n', 'b'] as const;
      for (let i = 0; i < 8; i++) {
        for (let j = 0; j < 8; j++) {
          const piece = board[i][j];
          if (piece && developedPieces.includes(piece.type as PieceType)) {
            score += piece.color === 'w' ? 20 : -20;
          }
        }
      }
    }
    
    return score;
  };

  // Evaluate piece mobility
  const evaluateMobility = (gameState: Chess) => {
    const moves = gameState.moves({ verbose: true });
    let mobilityScore = 0;

    for (const move of moves) {
      // Bonus for moves that attack center
      if (CENTER_SQUARES.includes(move.to)) {
        mobilityScore += 5;
      }
      // Bonus for captures
      if (move.captured) {
        mobilityScore += 10;
      }
      // Bonus for checks
      if (move.san.includes('+')) {
        mobilityScore += 15;
      }
    }

    return mobilityScore * (gameState.turn() === 'w' ? 1 : -1);
  };

  // Quiescence search to handle tactical sequences
  const quiescenceSearch = (alpha: number, beta: number, depth: number): number => {
    const standPat = evaluatePosition();
    
    if (depth === 0) return standPat;
    
    if (standPat >= beta) return beta;
    if (alpha < standPat) alpha = standPat;

    const moves = game.moves({ verbose: true }).filter(move => move.captured);
    moves.sort((a, b) => {
      const aValue = PIECE_VALUES[a.captured!] - PIECE_VALUES[a.piece];
      const bValue = PIECE_VALUES[b.captured!] - PIECE_VALUES[b.piece];
      return bValue - aValue;
    });

    for (const move of moves) {
      game.move(move);
      const score = -quiescenceSearch(-beta, -alpha, depth - 1);
      game.undo();

      if (score >= beta) return beta;
      if (score > alpha) alpha = score;
    }

    return alpha;
  };

  // Principal Variation Search with iterative deepening
  const getPositionKey = (game: Chess): string => {
    return game.fen().split(' ').slice(0, 4).join(' ');
  };

  // Helper functions for pawn and king evaluation
  const hasOpponentPawnInFront = (board: any[][], row: number, col: number, color: 'w' | 'b') => {
    const direction = color === 'w' ? -1 : 1;
    const startRow = row + direction;
    const endRow = color === 'w' ? 0 : 7;

    for (let r = startRow; color === 'w' ? r >= endRow : r <= endRow; r += direction) {
      for (let c = col - 1; c <= col + 1; c++) {
        if (c >= 0 && c < 8) {
          const piece = board[r][c];
          if (piece && piece.type === 'p' && piece.color !== color) {
            return true;
          }
        }
      }
    }
    return false;
  };

  const countPawnShield = (board: any[][], kingRow: number, kingCol: number, color: 'w' | 'b') => {
    let count = 0;
    const direction = color === 'w' ? -1 : 1;
    const pawnRow = kingRow + direction;

    if (pawnRow < 0 || pawnRow >= 8) return count;

    for (let col = Math.max(0, kingCol - 1); col <= Math.min(7, kingCol + 1); col++) {
      const piece = board[pawnRow][col];
      if (piece && piece.type === 'p' && piece.color === color) {
        count++;
      }
    }
    return count;
  };

  // Modify pvSearch to use transposition table
  const pvSearch = (depth: number, alpha: number, beta: number): number => {
    if (depth === 0 || game.isGameOver()) {
      return evaluatePosition();
    }

    const moves = game.moves({ verbose: true }) as ChessMoveWithPosition[];
    if (moves.length === 0) {
      if (game.isCheckmate()) return -100000;
      return 0; // Draw
    }

    let currentAlpha = alpha;
    
    for (const move of moves) {
      game.move(move);
      const score = -pvSearch(depth - 1, -beta, -currentAlpha);
      game.undo();

      if (score >= beta) {
        return beta;
      }
      
      currentAlpha = Math.max(currentAlpha, score);
    }

    return currentAlpha;
  };

  // Modify makeAiMove
  const makeAiMove = useCallback(async () => {
    try {
      if (game.isGameOver() || game.turn() === 'w') {
        console.log('AI move skipped: game over or not AI turn');
        return false;
      }

      // Use Stockfish for difficulty level 4
      if (computerDifficulty === 4) {
        console.log('Using Stockfish for move');
        try {
          const stockfishMove = await getStockfishMove(game.fen());
          if (stockfishMove) {
            console.log('Stockfish move:', stockfishMove);
            return makeMove(stockfishMove.from as Square, stockfishMove.to as Square);
          }
        } catch (error) {
          console.error('Stockfish move failed:', error);
          // Fall back to regular AI if Stockfish fails
        }
      }

      // Regular AI implementation for difficulty levels 1-3
      const moves = game.moves({ verbose: true }) as ChessMoveWithPosition[];
      if (moves.length === 0) {
        console.log('No valid moves available');
        return false;
      }

      const startTime = Date.now();
      const timeLimit = 2000 + (computerDifficulty * 1000); // Increased time limit
      let bestMove = moves[0];
      let bestScore = -Infinity;

      // Clear transposition table periodically to prevent memory issues
      if (game.moveNumber() % 10 === 0) {
        transpositionTable.clear();
      }

      // Iterative deepening with dynamic depth based on position complexity
      const baseDepth = 3 + computerDifficulty;
      const positionComplexity = Math.min(moves.length / 20, 1);
      const maxDepth = Math.floor(baseDepth + (positionComplexity * 2));

      console.log(`Starting AI search with depth ${maxDepth}, time limit ${timeLimit}ms`);

      for (let depth = 1; depth <= maxDepth; depth++) {
        if (Date.now() - startTime > timeLimit) {
          console.log(`Time limit reached at depth ${depth}`);
          break;
        }

        let currentBestScore = -Infinity;
        let currentBestMove = moves[0];

        // Sort moves based on previous iteration's scores and piece values
        if (depth > 1) {
          moves.sort((a, b) => {
            // Prioritize captures
            if (a.captured && !b.captured) return -1;
            if (!a.captured && b.captured) return 1;
            
            // Consider piece values
            const aValue = a.captured ? PIECE_VALUES[a.captured] : 0;
            const bValue = b.captured ? PIECE_VALUES[b.captured] : 0;
            
            // Consider piece development
            const aDevelopment = ['n', 'b'].includes(a.piece) ? 1 : 0;
            const bDevelopment = ['n', 'b'].includes(b.piece) ? 1 : 0;
            
            // Consider center control
            const aCenter = CENTER_SQUARES.includes(a.to) ? 1 : 0;
            const bCenter = CENTER_SQUARES.includes(b.to) ? 1 : 0;
            
            return (bValue + bDevelopment + bCenter) - (aValue + aDevelopment + aCenter);
          });
        }

        for (const move of moves) {
          game.move(move);
          const score = -pvSearch(depth - 1, -Infinity, Infinity);
          game.undo();

          const moveKey = getPositionKey(game) + move.from + move.to;
          transpositionTable.set(moveKey, { depth, score, flag: 'exact' });

          if (score > currentBestScore) {
            currentBestScore = score;
            currentBestMove = move;
          }
        }

        if (Date.now() - startTime <= timeLimit) {
          bestMove = currentBestMove;
          bestScore = currentBestScore;
          console.log(`Depth ${depth} completed, best score: ${bestScore}`);
        }
      }

      console.log(`AI move selected: ${bestMove.san}`);
      return makeMove(bestMove.from, bestMove.to);
    } catch (error) {
      console.error('Error in makeAiMove:', error);
      return false;
    }
  }, [game, computerDifficulty, makeMove, transpositionTable]);

  const handleSquareClick = useCallback((square: Square) => {
    const piece = game.get(square);

    if (selectedSquare === null) {
      if (piece && piece.color === game.turn()) {
        setSelectedSquare(square);
      }
    } else {
      if (square === selectedSquare) {
        setSelectedSquare(null);
      } else if (piece && piece.color === game.turn()) {
        setSelectedSquare(square);
      } else if (isValidMove(selectedSquare, square)) {
        makeMove(selectedSquare, square);
      } else {
        setSelectedSquare(null);
      }
    }
  }, [game, selectedSquare, isValidMove, makeMove]);

  const undoMove = useCallback(() => {
    const moveCount = game.history().length;
    if (computerDifficulty > 0 && moveCount >= 2) {
      game.undo();
      game.undo();
    } else if (moveCount >= 1) {
      game.undo();
    }
    setSelectedSquare(null);
    setLastMove(null);
  }, [game, computerDifficulty]);

  const resetGame = useCallback(() => {
    game.reset();
    setSelectedSquare(null);
    setLastMove(null);
    setMoveHistory([]);
    setCapturedPieces({ white: [], black: [] });
  }, [game]);

  // Clean up Stockfish worker when component unmounts
  useEffect(() => {
    return () => {
      if (computerDifficulty === 4) {
        terminateStockfish();
      }
    };
  }, [computerDifficulty]);

  return {
    board: getBoardState(),
    selectedSquare,
    lastMove,
    moveHistory,
    gameStatus: getGameStatus(),
    currentTurn: game.turn(),
    capturedPieces,
    handleSquareClick,
    undoMove,
    resetGame,
    makeAiMove
  };
}; 