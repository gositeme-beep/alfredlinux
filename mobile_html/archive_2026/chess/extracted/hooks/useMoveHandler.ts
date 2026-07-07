import { useCallback } from 'react';
import { Chess } from 'chess.js';
import { ChessMove, MovePosition } from '../types';

interface UseMoveHandlerProps {
  game: Chess;
  onMove: (move: ChessMove) => void;
}

export const useMoveHandler = ({ game, onMove }: UseMoveHandlerProps) => {
  const handleMove = useCallback((move: ChessMove) => {
    try {
      // Validate the move
      const moves = game.moves({ verbose: true });
      const isValidMove = moves.some(
        m => m.from === move.from && m.to === move.to && m.promotion === move.promotion
      );

      if (!isValidMove) {
        console.error('Invalid move attempted');
        return false;
      }

      const result = game.move({
        from: move.from,
        to: move.to,
        promotion: move.promotion,
      });

      if (result) {
        onMove(move);
        return true;
      }
    } catch (error) {
      console.error('Invalid move:', error);
    }
    return false;
  }, [game, onMove]);

  const handleSquareClick = useCallback((square: MovePosition) => {
    const piece = game.get(square);
    if (piece && piece.color === game.turn()) {
      const moves = game.moves({ square, verbose: true });
      return moves.map(move => move.to);
    }
    return [];
  }, [game]);

  return {
    handleMove,
    handleSquareClick,
  };
};
