
import { PieceColor } from '../types';
import { ColorTheme } from '../components/ThemeSelector';

export function useBoardStyles(inCheck: PieceColor | null, winner: PieceColor | null, colorTheme: ColorTheme) {
  // Get border color based on game state
  const getBorderColor = () => {
    if (winner) {
      return winner === 'white' 
        ? 'border-amber-300' 
        : winner === 'black' 
          ? 'border-blue-500' 
          : 'border-gray-500';
    }
    
    return inCheck ? 'border-red-500 animate-pulse' : 'border-amber-950/70';
  };
  
  // Get background based on theme
  const getBoardBackground = () => {
    switch(colorTheme) {
      case 'tournament':
        return 'bg-gradient-to-br from-green-800/20 to-green-600/20';
      case 'emerald':
        return 'bg-gradient-to-br from-emerald-900/20 to-emerald-700/20';
      case 'classic':
        return 'bg-gradient-to-br from-gray-900/20 to-gray-700/20';
      case 'wood':
        return 'bg-gradient-to-br from-amber-900/20 to-amber-700/20';
      default:
        return 'bg-gradient-to-br from-amber-900/20 to-amber-700/20';
    }
  };

  return {
    getBorderColor,
    getBoardBackground
  };
}
