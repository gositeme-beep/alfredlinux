
import { BoardPosition, PieceColor } from '../../types';
import { PIECE_VALUES } from './constants';
import { minimax } from './minimax';
import { simulateMove } from './moveSimulation';

// ADVANCED LEVEL - uses minimax but with more errors and less depth
export function getAdvancedMove(
  allMoves: { from: BoardPosition, to: BoardPosition }[],
  boardState: BoardPosition[],
  currentTurn: PieceColor,
  searchDepth: number = 2
): { from: BoardPosition, to: BoardPosition } {
  console.log(`Using advanced move selection with depth ${searchDepth}`);
  
  // Sort moves to check captures first (better alpha-beta pruning)
  allMoves.sort((a, b) => {
    const captureValueA = a.to.piece ? PIECE_VALUES[a.to.piece.type] : 0;
    const captureValueB = b.to.piece ? PIECE_VALUES[b.to.piece.type] : 0;
    return captureValueB - captureValueA;
  });
  
  // Sometimes make a mistake (20% chance)
  if (Math.random() < 0.2) {
    console.log("Advanced AI deliberately making a suboptimal move");
    // Randomly select from the top 5 capturing moves, or any move if no captures
    const topMoves = allMoves.filter(move => move.to.piece).slice(0, 5);
    if (topMoves.length > 0) {
      return topMoves[Math.floor(Math.random() * topMoves.length)];
    } else {
      return allMoves[Math.floor(Math.random() * allMoves.length)];
    }
  }
  
  // Regular minimax evaluation for best move
  let bestScore = -Infinity;
  let bestMove = null;
  
  // Evaluate only first 10 moves for efficiency in advanced mode
  const movsToEvaluate = allMoves.slice(0, 10);
  
  for (const move of movsToEvaluate) {
    try {
      const newBoard = simulateMove(boardState, move.from, move.to);
      const score = minimax(
        newBoard,
        searchDepth - 1,
        -Infinity,
        Infinity,
        false,
        currentTurn
      );
      
      if (score > bestScore) {
        bestScore = score;
        bestMove = move;
      }
    } catch (error) {
      console.error("Error evaluating move:", error);
    }
  }
  
  console.log(`Advanced AI best move has score: ${bestScore}`);
  return bestMove || allMoves[0];
}
