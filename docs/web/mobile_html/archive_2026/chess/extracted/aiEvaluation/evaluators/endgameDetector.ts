
import { BoardPosition } from '../../../types';
import { calculateNonPawnMaterial } from './materialEvaluator';

// Determine if the game is in endgame phase based on material
export function isEndgame(board: BoardPosition[]): boolean {
  // Count material value for both sides
  const whiteQueens = board.filter(pos => pos.piece && pos.piece.type === 'queen' && pos.piece.color === 'white').length;
  const blackQueens = board.filter(pos => pos.piece && pos.piece.type === 'queen' && pos.piece.color === 'black').length;
  
  // If both sides have no queens or only one side has a queen but few other pieces, it's endgame
  if (whiteQueens === 0 && blackQueens === 0) {
    return true;
  }
  
  // Calculate total material excluding kings and pawns
  const whiteMaterial = calculateNonPawnMaterial(board, 'white');
  const blackMaterial = calculateNonPawnMaterial(board, 'black');
  
  // If total material is below threshold, consider it endgame
  return (whiteMaterial + blackMaterial < 2500);
}
