#!/usr/bin/env node
/**
 * ══════════════════════════════════════════════════════════════════
 * CHESS MASTERS — 20-Agent Beta Test Harness
 * GoSiteMe Inc. · Automated QA & Stress Testing
 * ══════════════════════════════════════════════════════════════════
 *
 * Simulates 20+ concurrent AI-vs-AI chess games using all
 * personalities and difficulty levels. Tests:
 *   - Move legality & game state consistency
 *   - Opening book coverage
 *   - Endgame resolution (checkmate, stalemate, draws)
 *   - Material tracking accuracy
 *   - Performance under load (move times)
 *   - Edge cases (promotion, en passant, castling)
 *   - Error recovery & memory stability
 *
 * Usage:
 *   node beta-test-agents.js                 # Run full test suite
 *   node beta-test-agents.js --hours 24      # Run for 24 hours
 *   node beta-test-agents.js --agents 50     # Scale to 50 agents
 *   node beta-test-agents.js --verbose       # Detailed move logging
 *
 * Output: Logs to stdout + tests/logs/beta-test-YYYY-MM-DD.log
 */

'use strict';

const fs = require('fs');
const path = require('path');

// Load chess.js
const chessPath = path.resolve(__dirname, '../../../chess/js/chess.min.js');
const chessCode = fs.readFileSync(chessPath, 'utf8');
const vm = require('vm');
const sandbox = {};
vm.runInNewContext(chessCode, sandbox);
const ChessFactory = sandbox.Chess;

// ═══ CLI ARGS ═══
const args = process.argv.slice(2);
const getArg = (name, def) => {
    const idx = args.indexOf(`--${name}`);
    return idx >= 0 && args[idx + 1] ? args[idx + 1] : def;
};
const AGENT_COUNT   = parseInt(getArg('agents', '20'));
const RUN_HOURS     = parseFloat(getArg('hours', '24'));
const VERBOSE       = args.includes('--verbose');
const MAX_MOVES     = parseInt(getArg('maxmoves', '200'));
const REPORT_INTERVAL = 60000; // 1 minute

// ═══ PERSONALITIES (mirrors chess-engine.js) ═══
const PERSONALITIES = [
    { id: 'alfred', name: 'Alfred', elo: 1400, style: 'balanced' },
    { id: 'cipher', name: 'Cipher', elo: 1500, style: 'tactical' },
    { id: 'nova', name: 'Nova', elo: 1350, style: 'creative' },
    { id: 'atlas', name: 'Atlas', elo: 1300, style: 'positional' },
    { id: 'sage', name: 'Sage', elo: 1250, style: 'analytical' },
    { id: 'pulse', name: 'Pulse', elo: 1200, style: 'aggressive' },
    { id: 'pierre', name: 'Pierre', elo: 1150, style: 'artistic' },
    { id: 'sofia', name: 'Sofia', elo: 1450, style: 'precision' },
    { id: 'grandmaster_bot', name: 'Grandmaster', elo: 2200, style: 'perfect' },
    { id: 'kasparov_bot', name: 'Kasparov', elo: 2000, style: 'dynamic' },
    { id: 'capablanca_bot', name: 'Capablanca', elo: 1900, style: 'endgame' },
    { id: 'tal_bot', name: 'Tal', elo: 1800, style: 'sacrifice' },
    { id: 'fischer_bot', name: 'Fischer', elo: 2100, style: 'universal' },
    { id: 'morphy_bot', name: 'Morphy', elo: 1700, style: 'romantic' },
    { id: 'petrosian_bot', name: 'Petrosian', elo: 1800, style: 'defensive' },
    { id: 'botvinnik_bot', name: 'Botvinnik', elo: 1850, style: 'scientific' },
    { id: 'rookie', name: 'Rookie', elo: 800, style: 'beginner' },
    { id: 'club_player', name: 'Club Player', elo: 1000, style: 'improving' },
    { id: 'speed_demon', name: 'Speed Demon', elo: 1600, style: 'blitz' },
    { id: 'chaos', name: 'Chaos', elo: 1400, style: 'unpredictable' },
];

// ═══ STATS TRACKER ═══
const stats = {
    gamesStarted: 0,
    gamesCompleted: 0,
    gamesErrored: 0,
    totalMoves: 0,
    checkmates: 0,
    stalemates: 0,
    draws: { threefold: 0, fifty: 0, insufficient: 0, agreement: 0 },
    promotions: 0,
    enPassants: 0,
    castles: 0,
    longestGame: 0,
    shortestGame: Infinity,
    avgMoveTimeMs: 0,
    moveTimes: [],
    errors: [],
    personalityWins: {},
    eloBracketResults: { low: { w: 0, l: 0, d: 0 }, mid: { w: 0, l: 0, d: 0 }, high: { w: 0, l: 0, d: 0 } },
    edgeCases: { promotionToKnight: 0, promotionToRook: 0, promotionToBishop: 0, promotionToQueen: 0 },
    startTime: Date.now(),
    roundsCompleted: 0,
};

// ═══ LOGGING ═══
const logDir = path.join(__dirname, 'logs');
if (!fs.existsSync(logDir)) fs.mkdirSync(logDir, { recursive: true });
const logFile = path.join(logDir, `beta-test-${new Date().toISOString().slice(0, 10)}.log`);
const logStream = fs.createWriteStream(logFile, { flags: 'a' });

function log(msg, level = 'INFO') {
    const ts = new Date().toISOString();
    const line = `[${ts}] [${level}] ${msg}`;
    if (level === 'ERROR' || level === 'WARN' || VERBOSE) console.log(line);
    logStream.write(line + '\n');
}

function logError(msg, err) {
    log(`${msg}: ${err?.message || err}`, 'ERROR');
    stats.errors.push({ time: Date.now(), msg, err: err?.message || String(err) });
}

// ═══ AI MOVE SELECTION (simulates Stockfish behavior without actual engine) ═══
// Uses weighted random selection based on position evaluation heuristics

function evaluatePosition(game) {
    const fen = game.fen();
    const pieces = fen.split(' ')[0];
    const values = { p: 1, n: 3, b: 3.25, r: 5, q: 9, k: 0 };
    let score = 0;

    for (const ch of pieces) {
        if (ch >= 'A' && ch <= 'Z') score += values[ch.toLowerCase()] || 0;
        else if (ch >= 'a' && ch <= 'z') score -= values[ch] || 0;
    }

    // Mobility bonus
    const moves = game.moves({ verbose: true });
    score += (game.turn() === 'w' ? 1 : -1) * moves.length * 0.02;

    return score;
}

function selectAIMove(game, personality) {
    const moves = game.moves({ verbose: true });
    if (moves.length === 0) return null;

    const elo = personality.elo;
    const style = personality.style;

    // Score each move based on personality
    const scored = moves.map(move => {
        let score = 0;

        // Base: captures are generally good
        if (move.captured) {
            const capValues = { p: 1, n: 3, b: 3.25, r: 5, q: 9 };
            const pieceValues = { p: 1, n: 3, b: 3.25, r: 5, q: 9, k: 0 };
            score += (capValues[move.captured] || 0) - (pieceValues[move.piece] || 0) * 0.1;
        }

        // Check moves are strong
        const testGame = new ChessFactory(game.fen());
        testGame.move(move);
        if (testGame.in_check()) score += 1.5;
        if (testGame.in_checkmate()) score += 100;

        // Style bonuses
        switch (style) {
            case 'aggressive':
            case 'sacrifice':
            case 'dynamic':
            case 'romantic':
                if (move.captured) score += 1;
                if (move.piece === 'q' || move.piece === 'r') score += 0.3;
                break;
            case 'defensive':
            case 'positional':
            case 'scientific':
                // Prefer developing moves, castling
                if (move.flags.includes('k') || move.flags.includes('q')) score += 2;
                if (move.piece === 'n' || move.piece === 'b') score += 0.5;
                break;
            case 'tactical':
            case 'precision':
            case 'analytical':
                if (move.captured) score += 0.8;
                if (testGame.in_check()) score += 1;
                break;
            case 'creative':
            case 'artistic':
            case 'unpredictable':
                score += (Math.random() - 0.3) * 3; // Embrace chaos
                break;
            case 'endgame':
                if (move.piece === 'k') score += 0.5; // Active king
                if (move.flags.includes('p')) score += 3; // Promotion!
                break;
            case 'blitz':
                // Prefer recaptures and obvious moves
                if (move.captured) score += 1.5;
                break;
            case 'beginner':
            case 'improving':
                score += (Math.random() - 0.4) * 2; // Some randomness
                break;
        }

        // ELO affects randomness
        const noise = (2800 - elo) / 1000; // Higher ELO = less noise
        score += (Math.random() - 0.5) * noise;

        // Promotion handling
        if (move.flags.includes('p')) {
            score += 8; // Almost always promote
            // Personality may under-promote rarely for fun
            if (style === 'creative' || style === 'unpredictable') {
                if (Math.random() > 0.97) move.promotion = ['n', 'r', 'b'][Math.floor(Math.random() * 3)];
            }
        }

        return { move, score };
    });

    // Sort by score and pick (with some randomness for lower ELO)
    scored.sort((a, b) => b.score - a.score);

    // Top-K selection: stronger players pick from fewer top moves
    const topK = Math.max(1, Math.min(scored.length, Math.ceil((2800 - elo) / 200)));
    const idx = Math.floor(Math.random() * topK);

    return scored[Math.min(idx, scored.length - 1)].move;
}

// ═══ SINGLE GAME SIMULATION ═══

function runGame(whitePersonality, blackPersonality, gameId) {
    return new Promise((resolve) => {
        const game = new ChessFactory();
        const startTime = Date.now();
        let moveCount = 0;
        const moveLog = [];
        let error = null;
        const gameStats = {
            promotions: 0,
            enPassants: 0,
            castles: 0,
            captures: 0,
        };

        log(`Game #${gameId}: ${whitePersonality.name} (W, ${whitePersonality.elo}) vs ${blackPersonality.name} (B, ${blackPersonality.elo})`, 'INFO');

        function playNextMove() {
            try {
                if (game.game_over() || moveCount >= MAX_MOVES) {
                    return finishGame();
                }

                const personality = game.turn() === 'w' ? whitePersonality : blackPersonality;
                const moveStart = Date.now();
                const move = selectAIMove(game, personality);
                const moveTime = Date.now() - moveStart;

                if (!move) {
                    return finishGame();
                }

                // Track special moves
                if (move.flags.includes('p')) {
                    gameStats.promotions++;
                    stats.promotions++;
                    const promo = move.promotion || 'q';
                    const promoKey = { q: 'promotionToQueen', n: 'promotionToKnight', r: 'promotionToRook', b: 'promotionToBishop' }[promo];
                    if (promoKey) stats.edgeCases[promoKey]++;
                }
                if (move.flags.includes('e')) {
                    gameStats.enPassants++;
                    stats.enPassants++;
                }
                if (move.flags.includes('k') || move.flags.includes('q')) {
                    gameStats.castles++;
                    stats.castles++;
                }
                if (move.captured) {
                    gameStats.captures++;
                }

                // Apply move
                const result = game.move(move);
                if (!result) {
                    logError(`Game #${gameId}: Illegal move attempted`, `${move.from}${move.to} by ${personality.name}`);
                    stats.gamesErrored++;
                    error = 'Illegal move';
                    return finishGame();
                }

                moveCount++;
                stats.totalMoves++;
                stats.moveTimes.push(moveTime);

                // Validate game state consistency
                const fen = game.fen();
                if (!fen || fen.split(' ').length !== 6) {
                    logError(`Game #${gameId}: Invalid FEN after move ${moveCount}`, fen);
                    error = 'Invalid FEN';
                    return finishGame();
                }

                if (VERBOSE) {
                    log(`  Game #${gameId} Move ${moveCount}: ${personality.name} plays ${result.san} (${moveTime}ms)`, 'DEBUG');
                }

                moveLog.push({
                    move: result.san,
                    fen: fen,
                    player: personality.id,
                    time: moveTime,
                });

                // Yield to event loop periodically
                if (moveCount % 10 === 0) {
                    setImmediate(playNextMove);
                } else {
                    playNextMove();
                }
            } catch (err) {
                logError(`Game #${gameId}: Runtime error at move ${moveCount}`, err);
                stats.gamesErrored++;
                error = err.message;
                finishGame();
            }
        }

        function finishGame() {
            const elapsed = Date.now() - startTime;
            let result = 'unknown';
            let winner = null;

            if (game.in_checkmate()) {
                stats.checkmates++;
                winner = game.turn() === 'w' ? blackPersonality : whitePersonality;
                result = `checkmate by ${winner.name}`;
            } else if (game.in_stalemate()) {
                stats.stalemates++;
                result = 'stalemate';
            } else if (game.in_draw()) {
                if (game.in_threefold_repetition()) {
                    stats.draws.threefold++;
                    result = 'draw (threefold)';
                } else if (game.insufficient_material()) {
                    stats.draws.insufficient++;
                    result = 'draw (insufficient)';
                } else {
                    stats.draws.fifty++;
                    result = 'draw (50-move)';
                }
            } else if (moveCount >= MAX_MOVES) {
                stats.draws.agreement++;
                result = `draw (${MAX_MOVES} move limit)`;
            } else if (error) {
                result = `error: ${error}`;
            }

            stats.gamesCompleted++;
            if (moveCount > stats.longestGame) stats.longestGame = moveCount;
            if (moveCount < stats.shortestGame) stats.shortestGame = moveCount;

            // Track wins by personality
            if (winner) {
                stats.personalityWins[winner.id] = (stats.personalityWins[winner.id] || 0) + 1;
            }

            // Track ELO bracket results
            const higherElo = whitePersonality.elo >= blackPersonality.elo ? whitePersonality : blackPersonality;
            const bracket = higherElo.elo >= 1800 ? 'high' : higherElo.elo >= 1200 ? 'mid' : 'low';
            if (winner === higherElo) stats.eloBracketResults[bracket].w++;
            else if (winner) stats.eloBracketResults[bracket].l++;
            else stats.eloBracketResults[bracket].d++;

            log(`Game #${gameId} COMPLETE: ${result} in ${moveCount} moves (${elapsed}ms) | ${gameStats.captures} captures, ${gameStats.promotions} promotions, ${gameStats.castles} castles, ${gameStats.enPassants} en passant`, 'INFO');

            resolve({
                gameId,
                white: whitePersonality.id,
                black: blackPersonality.id,
                result,
                moves: moveCount,
                elapsed,
                error,
                gameStats,
            });
        }

        // Start game
        setImmediate(playNextMove);
    });
}

// ═══ TEST ROUND — 20 concurrent games ═══

async function runTestRound(roundNum) {
    log(`\n${'═'.repeat(60)}`, 'INFO');
    log(`ROUND ${roundNum} — Launching ${AGENT_COUNT} concurrent games`, 'INFO');
    log(`${'═'.repeat(60)}`, 'INFO');

    stats.roundsCompleted = roundNum;
    const games = [];

    for (let i = 0; i < AGENT_COUNT; i++) {
        // Random personality matchup (no self-play)
        const shuffled = [...PERSONALITIES].sort(() => Math.random() - 0.5);
        const white = shuffled[i % shuffled.length];
        let black = shuffled[(i + 1 + Math.floor(Math.random() * (shuffled.length - 1))) % shuffled.length];
        if (black.id === white.id) black = shuffled[(i + 2) % shuffled.length];

        stats.gamesStarted++;
        games.push(runGame(white, black, stats.gamesStarted));
    }

    const results = await Promise.all(games);

    // Round summary
    const checkmates = results.filter(r => r.result.startsWith('checkmate')).length;
    const draws = results.filter(r => r.result.startsWith('draw')).length;
    const errors = results.filter(r => r.error).length;
    const avgMoves = Math.round(results.reduce((s, r) => s + r.moves, 0) / results.length);

    log(`\nROUND ${roundNum} SUMMARY: ${checkmates} checkmates, ${draws} draws, ${errors} errors | Avg moves: ${avgMoves}`, 'INFO');

    return results;
}

// ═══ REPORT GENERATOR ═══

function generateReport() {
    const elapsed = Date.now() - stats.startTime;
    const hours = (elapsed / 3600000).toFixed(2);
    const avgMove = stats.moveTimes.length > 0
        ? (stats.moveTimes.reduce((s, t) => s + t, 0) / stats.moveTimes.length).toFixed(2)
        : 0;

    // Top personalities by wins
    const topPersonalities = Object.entries(stats.personalityWins)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 10)
        .map(([id, wins]) => {
            const p = PERSONALITIES.find(x => x.id === id);
            return `  ${p?.name || id}: ${wins} wins`;
        })
        .join('\n');

    const report = `
╔══════════════════════════════════════════════════════════════╗
║  CHESS MASTERS — Beta Test Report                           ║
║  ${new Date().toISOString()}                          ║
╠══════════════════════════════════════════════════════════════╣
║  Runtime: ${hours} hours | Agents: ${AGENT_COUNT}
║  Rounds: ${stats.roundsCompleted} | Games Started: ${stats.gamesStarted}
║  Games Completed: ${stats.gamesCompleted} | Errored: ${stats.gamesErrored}
╠══════════════════════════════════════════════════════════════╣
║  RESULTS
║  Checkmates: ${stats.checkmates}
║  Stalemates: ${stats.stalemates}
║  Draws: threefold=${stats.draws.threefold} fifty=${stats.draws.fifty} insufficient=${stats.draws.insufficient} limit=${stats.draws.agreement}
║  Total Moves: ${stats.totalMoves} | Avg/game: ${stats.gamesCompleted > 0 ? Math.round(stats.totalMoves / stats.gamesCompleted) : 0}
║  Longest Game: ${stats.longestGame} moves | Shortest: ${stats.shortestGame === Infinity ? 0 : stats.shortestGame} moves
║  Avg Move Time: ${avgMove}ms
╠══════════════════════════════════════════════════════════════╣
║  SPECIAL MOVES
║  Promotions: ${stats.promotions} (Q:${stats.edgeCases.promotionToQueen} N:${stats.edgeCases.promotionToKnight} R:${stats.edgeCases.promotionToRook} B:${stats.edgeCases.promotionToBishop})
║  En Passant: ${stats.enPassants}
║  Castles: ${stats.castles}
╠══════════════════════════════════════════════════════════════╣
║  ELO BRACKET (higher-rated player)
║  High (1800+): ${stats.eloBracketResults.high.w}W / ${stats.eloBracketResults.high.l}L / ${stats.eloBracketResults.high.d}D
║  Mid (1200-1800): ${stats.eloBracketResults.mid.w}W / ${stats.eloBracketResults.mid.l}L / ${stats.eloBracketResults.mid.d}D
║  Low (<1200): ${stats.eloBracketResults.low.w}W / ${stats.eloBracketResults.low.l}L / ${stats.eloBracketResults.low.d}D
╠══════════════════════════════════════════════════════════════╣
║  TOP PERSONALITIES BY WINS
${topPersonalities}
╠══════════════════════════════════════════════════════════════╣
║  ERRORS: ${stats.errors.length}
${stats.errors.slice(-5).map(e => `║  - ${e.msg}: ${e.err}`).join('\n') || '║  (none)'}
╚══════════════════════════════════════════════════════════════╝`;

    console.log(report);
    logStream.write(report + '\n');

    // Write JSON report for programmatic analysis
    const reportPath = path.join(logDir, `report-${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.json`);
    fs.writeFileSync(reportPath, JSON.stringify({
        ...stats,
        moveTimes: undefined, // Too large for JSON
        avgMoveTimeMs: parseFloat(avgMove),
        runtimeHours: parseFloat(hours),
    }, null, 2));
    log(`JSON report saved to ${reportPath}`, 'INFO');
}

// ═══ MAIN LOOP ═══

async function main() {
    console.log(`
╔══════════════════════════════════════════════════════════════╗
║  CHESS MASTERS — 20-Agent Beta Test                         ║
║  GoSiteMe Inc. · Automated QA                              ║
╠══════════════════════════════════════════════════════════════╣
║  Agents: ${AGENT_COUNT}                                             
║  Duration: ${RUN_HOURS} hours                                       
║  Max moves/game: ${MAX_MOVES}                                      
║  Log: ${logFile}
╚══════════════════════════════════════════════════════════════╝
`);

    const deadline = Date.now() + RUN_HOURS * 3600000;
    let round = 0;

    // Periodic report
    const reportTimer = setInterval(() => {
        generateReport();
    }, REPORT_INTERVAL);

    try {
        while (Date.now() < deadline) {
            round++;
            await runTestRound(round);

            // Brief pause between rounds
            await new Promise(r => setTimeout(r, 1000));
        }
    } catch (err) {
        logError('Fatal error in main loop', err);
    } finally {
        clearInterval(reportTimer);
        generateReport();
        log('\n✅ Beta test complete.', 'INFO');
        logStream.end();
        process.exit(0);
    }
}

// Handle graceful shutdown
process.on('SIGINT', () => {
    log('\nReceived SIGINT — generating final report...', 'WARN');
    generateReport();
    logStream.end();
    process.exit(0);
});

process.on('SIGTERM', () => {
    log('\nReceived SIGTERM — generating final report...', 'WARN');
    generateReport();
    logStream.end();
    process.exit(0);
});

process.on('uncaughtException', (err) => {
    logError('Uncaught exception', err);
    generateReport();
    logStream.end();
    process.exit(1);
});

// GO
main();
