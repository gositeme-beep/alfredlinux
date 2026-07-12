import { useState, useCallback } from 'react';
import { Chess, Square } from 'chess.js';
import { BoardState, PieceType } from '../types';

export const useChessBoardState = (game: Chess) => {
  const [boardState, setBoardState] = useState<BoardState>(() => {
    const squares: BoardState = {};
    for (let i = 0; i < 8; i++) {
      for (let j = 0; j < 8; j++) {
        const square = String.fromCharCode(97 + j) + (8 - i) as Square;
        const piece = game.get(square);
        if (piece) {
          squares[square] = {
            type: piece.type.toUpperCase() as PieceType,
            color: piece.color,
          };
        }
      }
    }
    return squares;
  });

  const updateBoard = useCallback(() => {
    const newBoardState: BoardState = {};
    for (let i = 0; i < 8; i++) {
      for (let j = 0; j < 8; j++) {
        const square = String.fromCharCode(97 + j) + (8 - i) as Square;
        const piece = game.get(square);
        if (piece) {
          newBoardState[square] = {
            type: piece.type.toUpperCase() as PieceType,
            color: piece.color,
          };
        }
      }
    }
    setBoardState(newBoardState);
  }, [game]);

  return {
    boardState,
    updateBoard,
  };
};
