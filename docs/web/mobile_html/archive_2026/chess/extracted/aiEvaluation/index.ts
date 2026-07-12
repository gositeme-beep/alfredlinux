import { BoardPosition, ChessMove, PieceColor } from '../../types';
import { getValidMoves } from '../moveValidation';
import { getBeginnerMove } from './beginnerAI';
import { getIntermediateMove } from './intermediateAI';
import { getAdvancedMove } from './advancedAI';
import { getExpertMove } from './expertAI';
import { getStockfishMove } from './stockfishAI';

// Function to get all valid moves for a given color
function getAllValidMoves(boardState: BoardPosition[], color: PieceColor) {
  const allMoves: { from: BoardPosition, to: BoardPosition }[] = [];

  // Find all pieces of the current color
  const pieces = boardState.filter(pos => pos.piece && pos.piece.color === color);

  // For each piece, find all valid moves
  pieces.forEach(piecePos => {
    const validMoves = getValidMoves(piecePos, boardState, color);
    
    // Create a move object for each valid destination
    validMoves.forEach(movePos => {
      allMoves.push({
        from: piecePos,
        to: movePos
      });
    });
  });

  return allMoves;
}

// Main function to generate an AI move based on difficulty level
export async function generateAIMove(
  boardState: BoardPosition[], 
  color: PieceColor, 
  difficulty: number = 1
): Promise<ChessMove | null> {
  console.log(`Generating AI move for ${color} at difficulty ${difficulty}`);
  
  try {
    // Get all possible moves for the current color
    const allMoves = getAllValidMoves(boardState, color);
    
    if (allMoves.length === 0) {
      console.log("No valid moves found for AI");
      return null;
    }
    
    console.log(`AI has ${allMoves.length} possible moves`);
    
    let selectedMove;
    
    // Choose move based on difficulty level
    switch (difficulty) {
      case 0: // Beginner - random with slight preference for captures
        selectedMove = getBeginnerMove(allMoves);
        break;
      case 1: // Intermediate - material focused
        selectedMove = getIntermediateMove(allMoves, boardState, color);
        break;
      case 2: // Advanced - position evaluation
        selectedMove = getAdvancedMove(allMoves, boardState, color);
        break;
      case 3: // Expert - deeper search with minimax
        selectedMove = getExpertMove(allMoves, boardState, color);
        break;
      case 4: // Stockfish - strongest engine
        selectedMove = await getStockfishMove(boardState);
        break;
      default:
        // Default to beginner as fallback
        console.log(`Unknown difficulty level: ${difficulty}, defaulting to beginner`);
        selectedMove = getBeginnerMove(allMoves);
    }
    
    if (!selectedMove || !selectedMove.from || !selectedMove.to) {
      console.error("AI failed to select a valid move", selectedMove);
      // Fallback to random move if AI selection failed
      if (allMoves.length > 0) {
        selectedMove = allMoves[Math.floor(Math.random() * allMoves.length)];
      } else {
        return null;
      }
    }
    
    console.log(`AI selected move: ${selectedMove.from.row},${selectedMove.from.col} -> ${selectedMove.to.row},${selectedMove.to.col}`);
    
    // Return a proper ChessMove object
    return {
      from: selectedMove.from,
      to: selectedMove.to,
      piece: selectedMove.from.piece!,
      capturedPiece: selectedMove.to.piece || undefined
    };
  } catch (error) {
    console.error("Error generating AI move:", error);
    return null;
  }
}
