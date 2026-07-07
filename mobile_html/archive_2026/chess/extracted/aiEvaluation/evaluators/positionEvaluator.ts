
import { BoardPosition } from '../../../types';
import { 
  PAWN_POSITION_SCORES, 
  KNIGHT_POSITION_SCORES, 
  BISHOP_POSITION_SCORES,
  ROOK_POSITION_SCORES,
  QUEEN_POSITION_SCORES,
  KING_MIDDLEGAME_SCORES,
  KING_ENDGAME_SCORES
} from '../constants';

// Get the positional value of a piece based on its position
export function getPositionValue(position: BoardPosition, endgame: boolean): number {
  if (!position.piece) return 0;
  
  const { row, col, piece } = position;
  let value = 0;
  
  // Flip row index for black pieces to use the same position tables
  const adjustedRow = piece.color === 'white' ? row : 7 - row;
  
  // Add position value based on piece type
  switch (piece.type) {
    case 'pawn':
      value = PAWN_POSITION_SCORES[adjustedRow][col];
      break;
    case 'knight':
      value = KNIGHT_POSITION_SCORES[adjustedRow][col];
      break;
    case 'bishop':
      value = BISHOP_POSITION_SCORES[adjustedRow][col];
      break;
    case 'rook':
      value = ROOK_POSITION_SCORES[adjustedRow][col];
      break;
    case 'queen':
      value = QUEEN_POSITION_SCORES[adjustedRow][col];
      break;
    case 'king':
      // Use different tables for middlegame and endgame
      value = endgame 
        ? KING_ENDGAME_SCORES[adjustedRow][col] 
        : KING_MIDDLEGAME_SCORES[adjustedRow][col];
      break;
  }
  
  return value;
}
