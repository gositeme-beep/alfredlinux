import { useEffect, useState } from 'react';
import { Chess } from 'chess.js';
import { PieceColor } from '../types';

export const useGameEndConditions = (game: Chess) => {
  const [checkmate, setCheckmate] = useState(false);
  const [stalemate, setStalemate] = useState(false);
  const [inCheck, setInCheck] = useState(false);
  const [winner, setWinner] = useState<PieceColor | null>(null);

  useEffect(() => {
    setCheckmate(game.isCheckmate());
    setStalemate(game.isStalemate());
    setInCheck(game.isCheck());

    if (game.isCheckmate()) {
      // In chess.js, turn() returns 'w' or 'b', and it represents the side to move
      // If it's checkmate, the side to move has lost
      setWinner(game.turn() === 'w' ? 'b' : 'w');
    } else {
      setWinner(null);
    }
  }, [game]);

  return {
    checkmate,
    stalemate,
    inCheck,
    winner,
  };
};
