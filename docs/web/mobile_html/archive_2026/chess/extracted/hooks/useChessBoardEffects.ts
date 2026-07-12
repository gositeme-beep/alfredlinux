import { useState, useEffect } from 'react';
import { PieceColor, ChessMove, PlayerColorType } from '../types';
import { useToast } from '@/components/ui/use-toast';
import confetti from 'canvas-confetti';

interface ChessBoardEffectsProps {
  winner: PieceColor | null;
  onGameOver?: (winner: PieceColor) => void;
  playerColor: PlayerColorType;
  inCheck: boolean;
  moveHistory: ChessMove[];
  onMoveHistoryUpdate?: (moves: ChessMove[]) => void;
  currentTurn: PlayerColorType;
}

export function useChessBoardEffects({
  winner,
  onGameOver,
  playerColor,
  inCheck,
  moveHistory,
  onMoveHistoryUpdate,
  currentTurn
}: ChessBoardEffectsProps) {
  // State for animations and effects
  const [checkAnimation, setCheckAnimation] = useState(false);
  const [celebrateWinner, setCelebrateWinner] = useState(false);
  const { toast } = useToast();

  // Function to play chess sounds
  const playMoveSound = (type: 'move' | 'capture' | 'check' | 'win') => {
    const audio = new Audio(`/assets/sounds/chess-${type}.mp3`);
    audio.volume = 0.5;
    audio.play().catch(e => console.log('Audio play error:', e));
  };

  // Update move history when it changes
  useEffect(() => {
    if (onMoveHistoryUpdate && moveHistory.length > 0) {
      onMoveHistoryUpdate(moveHistory);
    }
  }, [moveHistory, onMoveHistoryUpdate]);
  
  // Handle winner effects
  useEffect(() => {
    if (winner && onGameOver) {
      onGameOver(winner);
      setCelebrateWinner(true);
      playMoveSound('win');
      
      // Enhanced toast for winner
      if (winner === playerColor) {
        toast({
          title: "Victory!",
          description: "Congratulations! You've won the game by checkmate!",
          variant: "default",
        });
      } else {
        toast({
          title: "Checkmate",
          description: `${winner === 'w' ? 'White' : 'Black'} has won the game.`,
          variant: "destructive",
        });
      }
    }
  }, [winner, onGameOver, playerColor, toast]);

  // Enhanced celebration effect when a player wins
  useEffect(() => {
    if (celebrateWinner && winner === playerColor) {
      const colors = winner === 'w' ? ['#ffffff', '#eeeeee', '#ffdf00', '#ffd700'] : ['#333333', '#111111', '#ffdf00', '#ffd700'];
      
      // Create more elaborate confetti
      const launchConfetti = () => {
        // Fire confetti from the bottom
        confetti({
          particleCount: 150,
          spread: 90,
          origin: { y: 0.6 },
          colors
        });
        
        // Fire from left
        setTimeout(() => {
          confetti({
            particleCount: 80,
            angle: 60,
            spread: 55,
            origin: { x: 0, y: 0.5 },
            colors
          });
        }, 250);
        
        // Fire from right
        setTimeout(() => {
          confetti({
            particleCount: 80,
            angle: 120,
            spread: 55,
            origin: { x: 1, y: 0.5 },
            colors
          });
        }, 400);
      };

      // Launch confetti multiple times with delays for a more impressive effect
      launchConfetti();
      
      const timer1 = setTimeout(() => launchConfetti(), 800);
      const timer2 = setTimeout(() => launchConfetti(), 1600);
      const timer3 = setTimeout(() => launchConfetti(), 2400);
      
      return () => {
        clearTimeout(timer1);
        clearTimeout(timer2);
        clearTimeout(timer3);
      };
    }
  }, [celebrateWinner, winner, playerColor]);
  
  // Trigger check animation when a king is in check
  useEffect(() => {
    if (inCheck) {
      setCheckAnimation(true);
      playMoveSound('check');
      
      // Show toast notification for check
      toast({
        title: "Check!",
        description: `${currentTurn === 'w' ? 'White' : 'Black'} king is in check!`,
        variant: "destructive",
        duration: 2000,
      });
      
      const timer = setTimeout(() => {
        setCheckAnimation(false);
      }, 1000);
      return () => clearTimeout(timer);
    }
  }, [inCheck, currentTurn, toast]);

  return {
    checkAnimation,
    playMoveSound,
    celebrateWinner
  };
}
