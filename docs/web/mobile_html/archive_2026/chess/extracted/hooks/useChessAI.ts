import { useCallback } from 'react';
import { Chess } from 'chess.js';
import { ChessMove } from '../types';

interface UseChessAIProps {
  game: Chess;
  aiDifficulty: number;
}

export const useChessAI = ({ game, aiDifficulty }: UseChessAIProps) => {
  const generateMove = useCallback((): ChessMove | null => {
    if (aiDifficulty === 0) return null;

    const moves = game.moves({ verbose: true });
    if (moves.length === 0) return null;

    // For now, just pick a random move
    // TODO: Implement difficulty-based move selection
    const randomMove = moves[Math.floor(Math.random() * moves.length)];
    
    return {
      from: randomMove.from,
      to: randomMove.to,
      promotion: randomMove.promotion,
    };
  }, [game, aiDifficulty]);

  return {
    generateMove,
  };
};
