
import { BoardPosition, ChessMove, PieceColor } from '../../types';
import { getValidMoves } from '../moveValidation';

// Collect all possible moves for a given board state and color
export function collectAllPossibleMoves(
  boardState: BoardPosition[], 
  currentTurn: PieceColor
): { from: BoardPosition, to: BoardPosition }[] {
  // Get all pieces of the current turn's color
  const pieces = boardState.filter(pos => pos.piece && pos.piece.color === currentTurn);
  
  if (pieces.length === 0) {
    console.log("No pieces found for AI to move");
    return [];
  }
  
  // Collect all possible moves
  let allMoves: { from: BoardPosition, to: BoardPosition }[] = [];
  
  pieces.forEach(piece => {
    const validMoves = getValidMoves(piece, boardState, currentTurn);
    validMoves.forEach(movePos => {
      allMoves.push({
        from: piece,
        to: movePos
      });
    });
  });
  
  return allMoves;
}
