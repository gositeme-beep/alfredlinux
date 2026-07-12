import { useEffect } from 'react';
import { BoardPosition, ChessMove } from '../types';

interface UseRemoteMoveProps {
  remoteMove: ChessMove | null;
  boardState: BoardPosition[];
  handleMove: (move: ChessMove) => void;
}

export function useRemoteMove({ remoteMove, boardState, handleMove }: UseRemoteMoveProps) {
  useEffect(() => {
    if (remoteMove) {
      handleMove(remoteMove);
    }
  }, [remoteMove, handleMove]);
}
