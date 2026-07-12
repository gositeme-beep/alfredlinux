
import { BoardPosition } from '../../types';

// Create a simulated board state after making a move
export function simulateMove(
  board: BoardPosition[],
  fromPos: BoardPosition,
  toPos: BoardPosition
): BoardPosition[] {
  // Create a deep copy of the board to avoid modifying the original
  const newBoard = JSON.parse(JSON.stringify(board)) as BoardPosition[];
  
  // Find the positions in the new board
  const fromPosition = newBoard.find(pos => pos.row === fromPos.row && pos.col === fromPos.col);
  const toPosition = newBoard.find(pos => pos.row === toPos.row && pos.col === toPos.col);
  
  if (!fromPosition || !toPosition || !fromPosition.piece) {
    console.error("Invalid move simulation", { fromPos, toPos });
    return newBoard; // Return unmodified board to prevent errors
  }
  
  // Mark the piece as having moved
  fromPosition.piece.hasMoved = true;
  
  // Store the piece in a variable to ensure we don't lose it
  const movingPiece = { ...fromPosition.piece };
  
  // Handle special moves (simplified)
  
  // Handle pawn promotion - always promote to queen for AI evaluation
  if (movingPiece.type === 'pawn') {
    const isPromotionRank = 
      (movingPiece.color === 'white' && toPosition.row === 0) ||
      (movingPiece.color === 'black' && toPosition.row === 7);
    
    if (isPromotionRank) {
      movingPiece.type = 'queen';
    }
  }
  
  // Move the piece to the destination
  toPosition.piece = movingPiece;
  
  // Remove the piece from the original position
  fromPosition.piece = null;
  
  return newBoard;
}
