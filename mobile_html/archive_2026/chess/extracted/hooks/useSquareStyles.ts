
import { ColorTheme } from '../components/ThemeSelector';
import { 
  getSquareColor, 
  getTextureOverlay, 
  getHighlightClasses, 
  getCheckClasses,
  getLastMoveHighlight 
} from '../components/SquareTheme';

interface UseSquareStylesProps {
  isLight: boolean;
  isSelected: boolean;
  isValidMove: boolean;
  hasPiece: boolean;
  isInCheck: boolean;
  checkAnimation: boolean;
  colorTheme: ColorTheme;
  boardSize?: string;
  lastMove?: boolean;
}

export function useSquareStyles({
  isLight,
  isSelected,
  isValidMove,
  hasPiece,
  isInCheck,
  checkAnimation,
  colorTheme,
  boardSize = 'md',
  lastMove = false
}: UseSquareStylesProps): string {
  // Get square classes based on theme
  const squareClasses = `${getSquareColor(isLight, colorTheme)} ${getTextureOverlay(isLight, colorTheme)}`;
  
  // Add highlight classes
  const highlightClasses = getHighlightClasses(isSelected, isValidMove, hasPiece, isLight);
  
  // Add check animation
  const checkClasses = getCheckClasses(isInCheck, checkAnimation);
  
  // Add last move highlight
  const lastMoveClasses = lastMove ? getLastMoveHighlight(isLight, colorTheme) : '';
  
  // Add enhanced hover effect
  const hoverEffect = "hover:brightness-110 transition-all duration-150";
  
  // Add subtle border effect for better visual separation
  const borderEffect = "before:content-[''] before:absolute before:inset-0 before:box-border before:border before:border-black/5 before:pointer-events-none";
  
  return `cursor-pointer relative overflow-hidden ${squareClasses} ${highlightClasses} ${checkClasses} ${lastMoveClasses} ${hoverEffect} ${borderEffect}`;
}
