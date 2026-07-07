
import { BoardPosition, PieceColor } from '../../types';
import { PIECE_VALUES } from './constants';

// INTERMEDIATE LEVEL - random with strong preference for good captures and basic material value
export function getIntermediateMove(
  allMoves: { from: BoardPosition, to: BoardPosition }[],
  boardState: BoardPosition[],
  currentTurn: PieceColor = 'white'
): { from: BoardPosition, to: BoardPosition } {
  console.log("Using intermediate move selection");
  
  // Prefer captures with basic piece value awareness
  const captureMoves = allMoves.filter(move => move.to.piece);
  
  if (captureMoves.length > 0 && Math.random() < 0.7) {
    // 70% chance to make a capture when available
    // Prioritize higher value captures
    captureMoves.sort((a, b) => {
      const valueA = a.to.piece ? PIECE_VALUES[a.to.piece.type] : 0;
      const valueB = b.to.piece ? PIECE_VALUES[b.to.piece.type] : 0;
      return valueB - valueA;
    });
    
    // Select a random move but with bias toward the best captures
    const randomIndex = Math.floor(Math.random() * Math.min(3, captureMoves.length));
    return captureMoves[randomIndex];
  } else {
    // Random non-capture move, with some bias toward moving valuable pieces less
    allMoves.sort((a, b) => {
      const valueA = a.from.piece ? PIECE_VALUES[a.from.piece.type] : 0;
      const valueB = b.from.piece ? PIECE_VALUES[b.from.piece.type] : 0;
      // Slight preference to move less valuable pieces
      return valueA - valueB;
    });
    
    // Select more randomly from the first half of the sorted moves
    const randomIndex = Math.floor(Math.random() * Math.max(5, allMoves.length / 2));
    return allMoves[randomIndex];
  }
}
