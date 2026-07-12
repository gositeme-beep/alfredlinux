
import { BoardPosition, ChessMove, PieceColor } from '../../types';
import { PIECE_VALUES } from './constants';

// BEGINNER LEVEL - random moves with slight preference for captures and higher value captures
export function getBeginnerMove(allMoves: { from: BoardPosition, to: BoardPosition }[]): { from: BoardPosition, to: BoardPosition } {
  console.log("Using beginner (random) move selection with", allMoves.length, "possible moves");
  
  if (!allMoves || allMoves.length === 0) {
    console.error("No valid moves provided to beginner AI!");
    throw new Error("No valid moves provided to beginner AI");
  }
  
  try {
    // Filter out moves where source has no piece, which would be invalid
    const validMoves = allMoves.filter(move => 
      move.from && move.from.piece && 
      move.to
    );
    
    if (validMoves.length === 0) {
      console.error("No valid moves with pieces found for beginner AI!");
      // Return the first move as fallback
      return allMoves[0];
    }
    
    // Sometimes prefer captures based on piece value
    const captureMoves = validMoves.filter(move => move.to.piece);
    
    if (captureMoves.length > 0 && Math.random() < 0.8) {
      // Increased chance to make a capture when available (80%)
      console.log(`Beginner AI: Found ${captureMoves.length} possible capture moves`);
      
      // Sort captures by value of captured piece
      const sortedCaptures = [...captureMoves].sort((a, b) => {
        const valueA = a.to.piece ? PIECE_VALUES[a.to.piece.type] : 0;
        const valueB = b.to.piece ? PIECE_VALUES[b.to.piece.type] : 0;
        return valueB - valueA; // Higher value first
      });
      
      // Pick from top captures if available (with randomization)
      const topN = Math.min(sortedCaptures.length, 3);
      const selectedIndex = Math.floor(Math.random() * topN);
      const selectedMove = sortedCaptures[selectedIndex];
      
      console.log(`Beginner AI selected capture move: ${selectedMove.from.row},${selectedMove.from.col} -> ${selectedMove.to.row},${selectedMove.to.col}`);
      
      if (selectedMove.to.piece) {
        console.log(`Capturing: ${selectedMove.to.piece.type}, value: ${PIECE_VALUES[selectedMove.to.piece.type]}`);
      }
      
      return selectedMove;
    } else {
      // Fall back to random move from valid moves
      const selectedMove = validMoves[Math.floor(Math.random() * validMoves.length)];
      console.log(`Beginner AI selected random move: ${selectedMove.from.row},${selectedMove.from.col} -> ${selectedMove.to.row},${selectedMove.to.col}`);
      return selectedMove;
    }
  } catch (error) {
    console.error("Error in beginner AI move selection:", error);
    // Failsafe - just pick the first move to ensure AI always makes a move
    console.log("Using failsafe move selection - picking first available move");
    return allMoves[0];
  }
}
