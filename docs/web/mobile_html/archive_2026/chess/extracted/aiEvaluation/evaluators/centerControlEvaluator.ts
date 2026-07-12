
import { BoardPosition, PieceColor } from '../../../types';

// Evaluate center control
export function evaluateCenterControl(board: BoardPosition[], aiColor: PieceColor): number {
  const centerSquares = [
    {row: 3, col: 3}, {row: 3, col: 4}, 
    {row: 4, col: 3}, {row: 4, col: 4}
  ];
  
  let centerControlScore = 0;
  
  for (const centerPos of centerSquares) {
    const pos = board.find(p => p.row === centerPos.row && p.col === centerPos.col);
    if (pos && pos.piece) {
      const value = pos.piece.color === aiColor ? 10 : -10;
      centerControlScore += value;
    }
    
    // Also consider pieces that attack the center
    for (const boardPos of board) {
      if (boardPos.piece) {
        // TODO: Implement proper attack detection if needed
      }
    }
  }
  
  return centerControlScore;
}
