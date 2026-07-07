
import { BoardPosition, PieceColor } from '../../../types';

// Evaluate pawn structure
export function evaluatePawnStructure(board: BoardPosition[], aiColor: PieceColor): number {
  let pawnStructureScore = 0;
  
  // Get all pawns for each color
  const aiPawns = board.filter(pos => pos.piece && pos.piece.type === 'pawn' && pos.piece.color === aiColor);
  const opponentPawns = board.filter(pos => pos.piece && pos.piece.type === 'pawn' && pos.piece.color !== aiColor);
  
  // Penalize doubled pawns (pawns in the same column)
  const aiPawnColumns = aiPawns.map(p => p.col);
  const opponentPawnColumns = opponentPawns.map(p => p.col);
  
  // Count columns with multiple pawns
  const aiDoubledPawns = aiPawnColumns.filter((col, index) => aiPawnColumns.indexOf(col) !== index).length;
  const opponentDoubledPawns = opponentPawnColumns.filter((col, index) => opponentPawnColumns.indexOf(col) !== index).length;
  
  pawnStructureScore -= aiDoubledPawns * 20;
  pawnStructureScore += opponentDoubledPawns * 20;
  
  // TODO: Add isolated pawns penalty if needed
  
  return pawnStructureScore;
}
