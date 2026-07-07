
import { BoardPosition, PieceColor } from '../../types';
import { PIECE_VALUES } from './constants';
import { isEndgame } from './evaluators/endgameDetector';
import { getPositionValue } from './evaluators/positionEvaluator';
import { evaluateCenterControl } from './evaluators/centerControlEvaluator';
import { evaluatePawnStructure } from './evaluators/pawnStructureEvaluator';
import { calculateMaterialValue } from './evaluators/materialEvaluator';

// Board evaluation function
export function evaluateBoard(board: BoardPosition[], aiColor: PieceColor): number {
  let score = 0;
  const endgame = isEndgame(board);
  
  // Evaluate each position on the board
  for (const pos of board) {
    if (!pos.piece) continue;
    
    const pieceValue = PIECE_VALUES[pos.piece.type];
    const positionValue = getPositionValue(pos, endgame);
    
    // Add piece value and position value to score
    if (pos.piece.color === aiColor) {
      score += pieceValue + positionValue;
    } else {
      score -= pieceValue + positionValue;
    }
  }
  
  // Bonus for controlling the center
  score += evaluateCenterControl(board, aiColor);
  
  // Penalize isolated and doubled pawns
  score += evaluatePawnStructure(board, aiColor);
  
  return score;
}

// Re-export functions from evaluator modules
export { calculateMaterialValue } from './evaluators/materialEvaluator';
