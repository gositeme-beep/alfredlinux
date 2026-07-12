import { BoardPosition, PieceColor } from '../../types';
import { PIECE_VALUES } from './constants';
import { minimax } from './minimax';
import { simulateMove } from './moveSimulation';
import { evaluateBoard } from './boardEvaluation';
import { isKingInCheck } from '../moveValidation';
import { getValidMoves } from '../moveValidation';

// History heuristic table for move ordering
const historyTable: number[][][][] = Array(8).fill(0).map(() => 
  Array(8).fill(0).map(() => 
    Array(8).fill(0).map(() => 
      Array(8).fill(0)
    )
  )
);

// Killer moves table
const killerMoves: { from: BoardPosition, to: BoardPosition }[][] = Array(20).fill(0).map(() => []);

// EXPERT LEVEL - Enhanced implementation with advanced techniques
export function getExpertMove(
  allMoves: { from: BoardPosition, to: BoardPosition }[],
  boardState: BoardPosition[],
  currentTurn: PieceColor,
  searchDepth: number = 25
): { from: BoardPosition, to: BoardPosition } {
  console.log(`Using expert move selection with depth ${searchDepth}`);
  
  // Enhanced move ordering with killer moves and history heuristic
  allMoves.sort((a, b) => {
    // 1. Check for checks and checkmates
    const isCheckA = isMoveCheck(boardState, a);
    const isCheckB = isMoveCheck(boardState, b);
    if (isCheckA !== isCheckB) return isCheckA ? -1 : 1;
    
    // 2. Consider captures with SEE (Static Exchange Evaluation)
    const seeA = calculateSEE(boardState, a);
    const seeB = calculateSEE(boardState, b);
    if (seeA !== seeB) return seeB - seeA;
    
    // 3. Consider killer moves
    const isKillerA = isKillerMove(a, searchDepth);
    const isKillerB = isKillerMove(b, searchDepth);
    if (isKillerA !== isKillerB) return isKillerA ? -1 : 1;
    
    // 4. Consider history heuristic
    const historyA = getHistoryValue(a);
    const historyB = getHistoryValue(b);
    if (historyA !== historyB) return historyB - historyA;
    
    // 5. Consider piece values and development
    const pieceValueA = a.from.piece ? PIECE_VALUES[a.from.piece.type] : 0;
    const pieceValueB = b.from.piece ? PIECE_VALUES[b.from.piece.type] : 0;
    
    // 6. Consider center control and piece activity
    const centerValueA = getCenterValue(a.to);
    const centerValueB = getCenterValue(b.to);
    
    // 7. Consider piece activity and mobility
    const activityA = getPieceActivity(boardState, a.from);
    const activityB = getPieceActivity(boardState, b.from);
    
    // 8. Consider king safety
    const kingSafetyA = evaluateKingSafety(boardState, a, currentTurn);
    const kingSafetyB = evaluateKingSafety(boardState, b, currentTurn);
    
    // 9. Consider pawn structure
    const pawnStructureA = evaluatePawnStructure(boardState, a, currentTurn);
    const pawnStructureB = evaluatePawnStructure(boardState, b, currentTurn);
    
    // 10. Consider tempo and development
    const tempoA = evaluateTempo(boardState, a, currentTurn);
    const tempoB = evaluateTempo(boardState, b, currentTurn);
    
    // 11. Consider space control
    const spaceA = evaluateSpaceControl(boardState, a, currentTurn);
    const spaceB = evaluateSpaceControl(boardState, b, currentTurn);
    
    // 12. Consider piece coordination
    const coordinationA = evaluatePieceCoordination(boardState, a, currentTurn);
    const coordinationB = evaluatePieceCoordination(boardState, b, currentTurn);
    
    // 13. Consider pawn breaks
    const pawnBreaksA = evaluatePawnBreaks(boardState, a, currentTurn);
    const pawnBreaksB = evaluatePawnBreaks(boardState, b, currentTurn);
    
    // 14. Consider king activity in endgame
    const kingActivityA = evaluateKingActivity(boardState, a, currentTurn);
    const kingActivityB = evaluateKingActivity(boardState, b, currentTurn);
    
    // 15. Consider pattern recognition
    const patternA = evaluatePatterns(boardState, a, currentTurn);
    const patternB = evaluatePatterns(boardState, b, currentTurn);
    
    // 16. Consider piece mobility
    const mobilityA = evaluatePieceMobility(boardState, a, currentTurn);
    const mobilityB = evaluatePieceMobility(boardState, b, currentTurn);
    
    // 17. Consider piece threats
    const threatsA = evaluateThreats(boardState, a, currentTurn);
    const threatsB = evaluateThreats(boardState, b, currentTurn);
    
    // 18. Consider piece development
    const developmentA = evaluatePieceDevelopment(boardState, a, currentTurn);
    const developmentB = evaluatePieceDevelopment(boardState, b, currentTurn);
    
    return (centerValueB + activityB + kingSafetyB + pawnStructureB + tempoB + spaceB + 
            coordinationB + pawnBreaksB + kingActivityB + patternB + mobilityB + threatsB + developmentB) - 
           (centerValueA + activityA + kingSafetyA + pawnStructureA + tempoA + spaceA + 
            coordinationA + pawnBreaksA + kingActivityA + patternA + mobilityA + threatsA + developmentA);
  });
  
  // Full evaluation of all reasonable moves
  let bestScore = -Infinity;
  let bestMove = null;
  
  // Evaluate top moves more deeply
  const topMoves = allMoves.slice(0, Math.min(15, allMoves.length));
  
  for (const move of topMoves) {
    try {
      const newBoard = simulateMove(boardState, move.from, move.to);
      
      // Use iterative deepening with quiescence search
      let score = -Infinity;
      for (let depth = 1; depth <= searchDepth; depth++) {
        const currentScore = quiescenceSearch(
        newBoard,
          depth,
        -Infinity,
        Infinity,
        false,
        currentTurn
      );
        
        // Add position evaluation to the score
        const positionScore = evaluateBoard(newBoard, currentTurn);
        const totalScore = currentScore + positionScore * 0.2;
        
        if (totalScore > score) {
          score = totalScore;
        }
      }
      
      // Update history heuristic
      updateHistoryValue(move, score);
      
      // Update killer moves if this move caused a cutoff
      if (score > bestScore) {
        updateKillerMoves(move, searchDepth);
      }
      
      console.log(`Move ${move.from.row},${move.from.col} -> ${move.to.row},${move.to.col}, Score: ${score}`);
      
      if (score > bestScore) {
        bestScore = score;
        bestMove = move;
      }
    } catch (error) {
      console.error("Error evaluating expert move:", error);
    }
  }
  
  console.log(`Expert AI best move has score: ${bestScore}`);
  return bestMove || allMoves[0];
}

// Helper function to check if a move is a killer move
function isKillerMove(move: { from: BoardPosition, to: BoardPosition }, depth: number): boolean {
  return killerMoves[depth].some(killer => 
    killer.from.row === move.from.row && 
    killer.from.col === move.from.col && 
    killer.to.row === move.to.row && 
    killer.to.col === move.to.col
  );
}

// Helper function to update killer moves
function updateKillerMoves(move: { from: BoardPosition, to: BoardPosition }, depth: number): void {
  // Add the move to the killer moves table
  killerMoves[depth].unshift(move);
  
  // Keep only the last 2 killer moves per depth
  if (killerMoves[depth].length > 2) {
    killerMoves[depth].pop();
  }
}

// Helper function to get history heuristic value
function getHistoryValue(move: { from: BoardPosition, to: BoardPosition }): number {
  return historyTable[move.from.row][move.from.col][move.to.row][move.to.col];
}

// Helper function to update history heuristic
function updateHistoryValue(move: { from: BoardPosition, to: BoardPosition }, score: number): void {
  historyTable[move.from.row][move.from.col][move.to.row][move.to.col] += score;
}

// Helper function to evaluate patterns
function evaluatePatterns(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): number {
  let patternScore = 0;
  
  // Check for discovered attacks
  if (isDiscoveredAttack(board, move, color)) {
    patternScore += 2;
  }
  
  // Check for pins
  if (isPin(board, move, color)) {
    patternScore += 1.5;
  }
  
  // Check for forks
  if (isFork(board, move, color)) {
    patternScore += 3;
  }
  
  // Check for skewers
  if (isSkewer(board, move, color)) {
    patternScore += 2.5;
  }
  
  return patternScore;
}

// Helper function to check for discovered attacks
function isDiscoveredAttack(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): boolean {
  const newBoard = simulateMove(board, move.from, move.to);
  const opponentColor = color === 'w' ? 'b' : 'w';
  
  // Check if moving the piece reveals an attack
  const pieces = newBoard.filter(pos => pos.piece?.color === color);
  for (const piece of pieces) {
    if (piece === move.to) continue;
    
    const moves = getValidMoves(piece, newBoard, color);
    for (const target of moves) {
      const targetPiece = newBoard.find(p => p.row === target.row && p.col === target.col)?.piece;
      if (targetPiece?.color === opponentColor && targetPiece.type !== 'k') {
        return true;
      }
    }
  }
  
  return false;
}

// Helper function to check for pins
function isPin(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): boolean {
  const newBoard = simulateMove(board, move.from, move.to);
  const opponentColor = color === 'w' ? 'b' : 'w';
  
  // Check if the move creates a pin
  const pieces = newBoard.filter(pos => pos.piece?.color === opponentColor);
  for (const piece of pieces) {
    const moves = getValidMoves(piece, newBoard, opponentColor);
    for (const target of moves) {
      const targetPiece = newBoard.find(p => p.row === target.row && p.col === target.col)?.piece;
      if (targetPiece?.color === color && targetPiece.type === 'k') {
        return true;
      }
    }
  }
  
  return false;
}

// Helper function to check for forks
function isFork(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): boolean {
  const newBoard = simulateMove(board, move.from, move.to);
  const opponentColor = color === 'w' ? 'b' : 'w';
  
  // Check if the move creates a fork
  const moves = getValidMoves(move.to, newBoard, color);
  let valuableTargets = 0;
  
  for (const target of moves) {
    const targetPiece = newBoard.find(p => p.row === target.row && p.col === target.col)?.piece;
    if (targetPiece?.color === opponentColor && targetPiece.type !== 'p') {
      valuableTargets++;
    }
  }
  
  return valuableTargets >= 2;
}

// Helper function to check for skewers
function isSkewer(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): boolean {
  const newBoard = simulateMove(board, move.from, move.to);
  const opponentColor = color === 'w' ? 'b' : 'w';
  
  // Check if the move creates a skewer
  const moves = getValidMoves(move.to, newBoard, color);
  for (const target of moves) {
    const targetPiece = newBoard.find(p => p.row === target.row && p.col === target.col)?.piece;
    if (targetPiece?.color === opponentColor && targetPiece.type !== 'k') {
      // Check if there's a more valuable piece behind
      const direction = {
        row: target.row - move.to.row,
        col: target.col - move.to.col
      };
      
      let currentRow = target.row + direction.row;
      let currentCol = target.col + direction.col;
      
      while (currentRow >= 0 && currentRow < 8 && currentCol >= 0 && currentCol < 8) {
        const behindPiece = newBoard.find(p => p.row === currentRow && p.col === currentCol)?.piece;
        if (behindPiece?.color === opponentColor && 
            PIECE_VALUES[behindPiece.type] > PIECE_VALUES[targetPiece.type]) {
          return true;
        }
        if (behindPiece) break;
        
        currentRow += direction.row;
        currentCol += direction.col;
      }
    }
  }
  
  return false;
}

// Quiescence search for better tactical evaluation
function quiescenceSearch(
  board: BoardPosition[],
  depth: number,
  alpha: number,
  beta: number,
  isMaximizing: boolean,
  color: PieceColor
): number {
  if (depth === 0) {
    return evaluateBoard(board, color);
  }
  
  // Get all captures and checks
  const moves = getAllMoves(board, color).filter(move => 
    move.to.piece || isMoveCheck(board, move)
  );
  
  if (moves.length === 0) {
    return evaluateBoard(board, color);
  }
  
  if (isMaximizing) {
    let maxScore = -Infinity;
    for (const move of moves) {
      const newBoard = simulateMove(board, move.from, move.to);
      const score = quiescenceSearch(newBoard, depth - 1, alpha, beta, false, color);
      maxScore = Math.max(maxScore, score);
      alpha = Math.max(alpha, score);
      if (beta <= alpha) break;
    }
    return maxScore;
  } else {
    let minScore = Infinity;
    for (const move of moves) {
      const newBoard = simulateMove(board, move.from, move.to);
      const score = quiescenceSearch(newBoard, depth - 1, alpha, beta, true, color);
      minScore = Math.min(minScore, score);
      beta = Math.min(beta, score);
      if (beta <= alpha) break;
    }
    return minScore;
  }
}

// Helper function to evaluate tempo
function evaluateTempo(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): number {
  let tempoScore = 0;
  
  // Check if move develops a piece
  if (move.from.piece?.type !== 'p' && !isPieceDeveloped(board, move.from)) {
    tempoScore += 2;
  }
  
  // Check if move controls center
  if (isCenterSquare(move.to)) {
    tempoScore += 1;
  }
  
  // Check if move threatens opponent's pieces
  const threats = evaluateThreats(board, move, color);
  tempoScore += threats;
  
  return tempoScore;
}

// Helper function to evaluate space control
function evaluateSpaceControl(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): number {
  let spaceScore = 0;
  
  // Check squares controlled by the piece
  const controlledSquares = getControlledSquares(board, move.to);
  spaceScore += controlledSquares.length * 0.5;
  
  // Check if move controls opponent's space
  const opponentColor = color === 'w' ? 'b' : 'w';
  const opponentSquares = getOpponentSquares(board, opponentColor);
  const controlledOpponentSquares = controlledSquares.filter(sq => 
    opponentSquares.some(os => os.row === sq.row && os.col === sq.col)
  );
  spaceScore += controlledOpponentSquares.length;
  
  return spaceScore;
}

// Helper function to check if a piece is developed
function isPieceDeveloped(board: BoardPosition[], pos: BoardPosition): boolean {
  if (!pos.piece) return false;
  
  // For non-pawn pieces, check if they've moved from starting position
  if (pos.piece.type !== 'p') {
    const startingRow = pos.piece.color === 'w' ? 0 : 7;
    return pos.row !== startingRow;
  }
  
  return true;
}

// Helper function to check if a square is in the center
function isCenterSquare(pos: BoardPosition): boolean {
  const centerSquares = [
    [3, 3], [3, 4], [4, 3], [4, 4],
    [2, 3], [2, 4], [3, 2], [3, 5],
    [4, 2], [4, 5], [5, 3], [5, 4]
  ];
  
  return centerSquares.some(([row, col]) => pos.row === row && pos.col === col);
}

// Helper function to evaluate threats
function evaluateThreats(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): number {
  let threatScore = 0;
  const opponentColor = color === 'w' ? 'b' : 'w';
  
  // Check if move threatens opponent's pieces
  const controlledSquares = getControlledSquares(board, move.to);
  for (const square of controlledSquares) {
    const piece = board.find(p => p.row === square.row && p.col === square.col)?.piece;
    if (piece && piece.color === opponentColor) {
      threatScore += PIECE_VALUES[piece.type] * 0.1;
    }
  }
  
  return threatScore;
}

// Helper function to get squares controlled by a piece
function getControlledSquares(board: BoardPosition[], pos: BoardPosition): BoardPosition[] {
  if (!pos.piece) return [];
  
  const moves = getValidMoves(pos, board, pos.piece.color);
  return moves;
}

// Helper function to get opponent's squares
function getOpponentSquares(board: BoardPosition[], color: PieceColor): BoardPosition[] {
  return board.filter(pos => pos.piece?.color === color);
}

// Helper function to calculate Static Exchange Evaluation
function calculateSEE(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }): number {
  if (!move.to.piece) return 0;
  
  const attackerValue = move.from.piece ? PIECE_VALUES[move.from.piece.type] : 0;
  const defenderValue = move.to.piece ? PIECE_VALUES[move.to.piece.type] : 0;
  
  // Basic SEE calculation
  return defenderValue - attackerValue;
}

// Helper function to evaluate king safety
function evaluateKingSafety(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): number {
  const newBoard = simulateMove(board, move.from, move.to);
  const kingPos = findKing(newBoard, color);
  
  if (!kingPos) return 0;
  
  let safetyScore = 0;
  
  // Check pawn shield
  const pawnShield = evaluatePawnShield(newBoard, kingPos, color);
  safetyScore += pawnShield;
  
  // Check open files near king
  const openFiles = evaluateOpenFiles(newBoard, kingPos);
  safetyScore -= openFiles * 2;
  
  // Check piece attacks on king's area
  const kingAttacks = evaluateKingAttacks(newBoard, kingPos, color);
  safetyScore -= kingAttacks;
  
  return safetyScore;
}

// Helper function to evaluate pawn structure
function evaluatePawnStructure(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): number {
  let score = 0;
  
  // Check for isolated pawns
  const isolatedPawns = countIsolatedPawns(board, color);
  score -= isolatedPawns * 2;
  
  // Check for doubled pawns
  const doubledPawns = countDoubledPawns(board, color);
  score -= doubledPawns * 1.5;
  
  // Check for passed pawns
  const passedPawns = countPassedPawns(board, color);
  score += passedPawns * 3;
  
  return score;
}

// Helper function to find king position
function findKing(board: BoardPosition[], color: PieceColor): BoardPosition | null {
  return board.find(pos => pos.piece?.type === 'k' && pos.piece?.color === color) || null;
}

// Helper function to evaluate pawn shield
function evaluatePawnShield(board: BoardPosition[], kingPos: BoardPosition, color: PieceColor): number {
  let shieldScore = 0;
  const row = kingPos.row;
  const col = kingPos.col;
  
  // Check pawns in front of king
  for (let i = -1; i <= 1; i++) {
    const shieldPos = board.find(pos => pos.row === row + (color === 'w' ? 1 : -1) && pos.col === col + i);
    if (shieldPos?.piece?.type === 'p' && shieldPos.piece.color === color) {
      shieldScore += 2;
    }
  }
  
  return shieldScore;
}

// Helper function to evaluate open files
function evaluateOpenFiles(board: BoardPosition[], kingPos: BoardPosition): number {
  const col = kingPos.col;
  
  // Check if file is open
  for (let row = 0; row < 8; row++) {
    const pos = board.find(p => p.row === row && p.col === col);
    if (pos?.piece?.type === 'p') {
      return 0; // File is not open
    }
  }
  
  return 1;
}

// Helper function to evaluate attacks on king's area
function evaluateKingAttacks(board: BoardPosition[], kingPos: BoardPosition, color: PieceColor): number {
  let attackScore = 0;
  const opponentColor = color === 'w' ? 'b' : 'w';
  
  // Check squares around king
  for (let i = -1; i <= 1; i++) {
    for (let j = -1; j <= 1; j++) {
      const pos = board.find(p => p.row === kingPos.row + i && p.col === kingPos.col + j);
      if (pos?.piece?.color === opponentColor) {
        attackScore += 1;
      }
    }
  }
  
  return attackScore;
}

// Helper function to count isolated pawns
function countIsolatedPawns(board: BoardPosition[], color: PieceColor): number {
  let isolatedCount = 0;
  
  for (let col = 0; col < 8; col++) {
    const hasPawn = board.some(pos => pos.col === col && pos.piece?.type === 'p' && pos.piece?.color === color);
    if (hasPawn) {
      const hasAdjacentPawn = board.some(pos => 
        (pos.col === col - 1 || pos.col === col + 1) && 
        pos.piece?.type === 'p' && 
        pos.piece?.color === color
      );
      if (!hasAdjacentPawn) {
        isolatedCount++;
      }
    }
  }
  
  return isolatedCount;
}

// Helper function to count doubled pawns
function countDoubledPawns(board: BoardPosition[], color: PieceColor): number {
  let doubledCount = 0;
  
  for (let col = 0; col < 8; col++) {
    const pawnsInFile = board.filter(pos => 
      pos.col === col && 
      pos.piece?.type === 'p' && 
      pos.piece?.color === color
    ).length;
    
    if (pawnsInFile > 1) {
      doubledCount += pawnsInFile - 1;
    }
  }
  
  return doubledCount;
}

// Helper function to count passed pawns
function countPassedPawns(board: BoardPosition[], color: PieceColor): number {
  let passedCount = 0;
  
  for (const pos of board) {
    if (pos.piece?.type === 'p' && pos.piece?.color === color) {
      const isPassed = !board.some(p => 
        p.piece?.type === 'p' && 
        p.piece?.color !== color && 
        Math.abs(p.col - pos.col) <= 1
      );
      
      if (isPassed) {
        passedCount++;
      }
    }
  }
  
  return passedCount;
}

// Helper function to check if a move puts the opponent in check
function isMoveCheck(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }): boolean {
  const newBoard = simulateMove(board, move.from, move.to);
  const opponentColor = move.from.piece?.color === 'w' ? 'b' : 'w';
  return isKingInCheck(newBoard, opponentColor);
}

// Helper function to evaluate center control
function getCenterValue(pos: BoardPosition): number {
  const centerSquares = [
    [3, 3], [3, 4], [4, 3], [4, 4]
  ];
  
  for (const [row, col] of centerSquares) {
    if (pos.row === row && pos.col === col) return 3; // Increased center value
  }
  
  const extendedCenter = [
    [2, 3], [2, 4], [3, 2], [3, 5],
    [4, 2], [4, 5], [5, 3], [5, 4]
  ];
  
  for (const [row, col] of extendedCenter) {
    if (pos.row === row && pos.col === col) return 2; // Increased extended center value
  }
  
  return 0;
}

// Helper function to evaluate piece activity
function getPieceActivity(board: BoardPosition[], pos: BoardPosition): number {
  if (!pos.piece) return 0;
  
  const validMoves = getValidMoves(pos, board, pos.piece.color);
  return validMoves.length * 0.7; // Increased mobility value
}

// Helper function to get all possible moves for a color
function getAllMoves(board: BoardPosition[], color: PieceColor): { from: BoardPosition, to: BoardPosition }[] {
  const moves: { from: BoardPosition, to: BoardPosition }[] = [];
  
  // Get all pieces of the given color
  const pieces = board.filter(pos => pos.piece?.color === color);
  
  // Generate moves for each piece
  for (const piece of pieces) {
    const validMoves = getValidMoves(piece, board, color);
    moves.push(...validMoves.map(move => ({
      from: piece,
      to: move
    })));
  }
  
  return moves;
}

// Helper function to evaluate piece coordination
function evaluatePieceCoordination(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): number {
  let coordinationScore = 0;
  
  // Check if pieces are working together
  const controlledSquares = getControlledSquares(board, move.to);
  const friendlyPieces = board.filter(pos => pos.piece?.color === color);
  
  for (const piece of friendlyPieces) {
    if (piece === move.from) continue;
    
    const pieceSquares = getControlledSquares(board, piece);
    const overlappingSquares = controlledSquares.filter(sq => 
      pieceSquares.some(ps => ps.row === sq.row && ps.col === sq.col)
    );
    
    coordinationScore += overlappingSquares.length * 0.5;
  }
  
  return coordinationScore;
}

// Helper function to evaluate pawn breaks
function evaluatePawnBreaks(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): number {
  if (move.from.piece?.type !== 'p') return 0;
  
  let breakScore = 0;
  const opponentColor = color === 'w' ? 'b' : 'w';
  
  // Check for potential pawn breaks
  const row = move.to.row;
  const col = move.to.col;
  
  // Check for pawn chains
  const hasPawnSupport = board.some(pos => 
    pos.piece?.type === 'p' && 
    pos.piece?.color === color && 
    Math.abs(pos.col - col) === 1 && 
    pos.row === row + (color === 'w' ? 1 : -1)
  );
  
  if (hasPawnSupport) {
    breakScore += 2;
  }
  
  // Check for potential passed pawns
  const isPassed = !board.some(pos => 
    pos.piece?.type === 'p' && 
    pos.piece?.color === opponentColor && 
    Math.abs(pos.col - col) <= 1
  );
  
  if (isPassed) {
    breakScore += 3;
  }
  
  return breakScore;
}

// Helper function to evaluate king activity in endgame
function evaluateKingActivity(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): number {
  if (move.from.piece?.type !== 'k') return 0;
  
  let activityScore = 0;
  const opponentColor = color === 'w' ? 'b' : 'w';
  
  // Check if it's an endgame
  const isEndgame = board.filter(pos => pos.piece).length <= 10;
  
  if (isEndgame) {
    // Encourage king to move towards center
    const centerValue = getCenterValue(move.to);
    activityScore += centerValue * 2;
    
    // Encourage king to move towards opponent's pawns
    const opponentPawns = board.filter(pos => 
      pos.piece?.type === 'p' && 
      pos.piece?.color === opponentColor
    );
    
    for (const pawn of opponentPawns) {
      const distance = Math.abs(pawn.row - move.to.row) + Math.abs(pawn.col - move.to.col);
      activityScore += (8 - distance) * 0.5;
    }
  }
  
  return activityScore;
}

// New helper function to evaluate piece mobility
function evaluatePieceMobility(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): number {
  const piece = move.from.piece;
  if (!piece) return 0;
  
  let mobility = 0;
  const validMoves = getValidMoves(move.from, board, color);
  
  // Count number of squares the piece can move to
  mobility += validMoves.length * 0.1;
  
  // Bonus for controlling center squares
  validMoves.forEach(validMove => {
    if (isCenterSquare(validMove)) {
      mobility += 0.2;
    }
  });
  
  // Bonus for controlling squares near opponent's king
  const opponentKing = findKing(board, color === 'w' ? 'b' : 'w');
  if (opponentKing) {
    validMoves.forEach(validMove => {
      const distance = Math.abs(validMove.row - opponentKing.row) + Math.abs(validMove.col - opponentKing.col);
      if (distance <= 2) {
        mobility += 0.3;
      }
    });
  }
  
  return mobility;
}

// New helper function to evaluate piece development
function evaluatePieceDevelopment(board: BoardPosition[], move: { from: BoardPosition, to: BoardPosition }, color: PieceColor): number {
  const piece = move.from.piece;
  if (!piece) return 0;
  
  let development = 0;
  
  // Bonus for developing pieces in the opening
  if (piece.type === 'n' || piece.type === 'b') {
    // Bonus for moving pieces from their starting positions
    if (color === 'w' && move.from.row === 0) {
      development += 0.5;
    } else if (color === 'b' && move.from.row === 7) {
      development += 0.5;
    }
    
    // Bonus for developing towards the center
    if (isCenterSquare(move.to)) {
      development += 0.3;
    }
  }
  
  // Bonus for castling
  if (piece.type === 'k' && Math.abs(move.from.col - move.to.col) === 2) {
    development += 1.0;
  }
  
  return development;
}
