import { BoardPosition, PieceColor } from '../../types';
import { getValidMoves } from '../moveValidation';
import { evaluateBoard } from './boardEvaluation';
import { simulateMove } from './moveSimulation';
import { PIECE_VALUES } from './constants';

// Minimax algorithm with alpha-beta pruning
export function minimax(
  board: BoardPosition[],
  depth: number,
  alpha: number,
  beta: number,
  isMaximizingPlayer: boolean,
  aiColor: PieceColor
): number {
  // Base case: return evaluation when at max depth or game over
  if (depth === 0) {
    return evaluateBoard(board, aiColor);
  }
  
  const currentTurn = isMaximizingPlayer ? aiColor : (aiColor === 'white' ? 'black' : 'white');
  
  // Get all pieces of the current turn's color
  const pieces = board.filter(pos => pos.piece && pos.piece.color === currentTurn);
  
  // Generate all possible moves
  let allMoves: { from: BoardPosition, to: BoardPosition }[] = [];
  pieces.forEach(piece => {
    const validMoves = getValidMoves(piece, board, currentTurn);
    validMoves.forEach(movePos => {
      allMoves.push({
        from: piece,
        to: movePos
      });
    });
  });
  
  // No moves available - could be checkmate or stalemate
  if (allMoves.length === 0) {
    // Simple evaluation for terminal node - add depth to prioritize faster checkmates
    return isMaximizingPlayer ? -10000 - depth * 100 : 10000 + depth * 100;
  }
  
  // Sort moves to improve alpha-beta pruning
  // Check captures first, and prioritize higher value captures
  allMoves.sort((a, b) => {
    const captureValueA = a.to.piece ? PIECE_VALUES[a.to.piece.type] : 0;
    const captureValueB = b.to.piece ? PIECE_VALUES[b.to.piece.type] : 0;
    
    // Also consider piece values for move ordering
    const pieceValueA = a.from.piece ? PIECE_VALUES[a.from.piece.type] : 0;
    const pieceValueB = b.from.piece ? PIECE_VALUES[b.from.piece.type] : 0;
    
    // Higher value captures and lower value attacking pieces first
    if (captureValueA !== captureValueB) {
      return captureValueB - captureValueA; // Higher value captures first
    }
    return pieceValueA - pieceValueB; // Lower value pieces attacking first
  });
  
  if (isMaximizingPlayer) {
    let maxEval = -Infinity;
    
    for (const move of allMoves) {
      const newBoard = simulateMove(board, move.from, move.to);
      const evalScore = minimax(newBoard, depth - 1, alpha, beta, false, aiColor);
      maxEval = Math.max(maxEval, evalScore);
      alpha = Math.max(alpha, evalScore);
      if (beta <= alpha) {
        break; // Beta cutoff
      }
    }
    
    return maxEval;
  } else {
    let minEval = Infinity;
    
    for (const move of allMoves) {
      const newBoard = simulateMove(board, move.from, move.to);
      const evalScore = minimax(newBoard, depth - 1, alpha, beta, true, aiColor);
      minEval = Math.min(minEval, evalScore);
      beta = Math.min(beta, evalScore);
      if (beta <= alpha) {
        break; // Alpha cutoff
      }
    }
    
    return minEval;
  }
}
