
import { BoardPosition, PieceColor } from '../../../types';
import { PIECE_VALUES } from '../constants';

// Calculate non-pawn material
export function calculateNonPawnMaterial(board: BoardPosition[], color: PieceColor): number {
  return board
    .filter(pos => pos.piece && 
             pos.piece.color === color && 
             pos.piece.type !== 'pawn' &&
             pos.piece.type !== 'king')
    .reduce((sum, pos) => sum + PIECE_VALUES[pos.piece!.type], 0);
}

// Utility function to calculate the material value of the board
export function calculateMaterialValue(board: BoardPosition[], color: PieceColor): number {
  return board
    .filter(pos => pos.piece && pos.piece.color === color)
    .reduce((sum, pos) => sum + PIECE_VALUES[pos.piece!.type], 0);
}
