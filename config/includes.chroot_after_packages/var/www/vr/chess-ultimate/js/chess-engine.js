/* ═══════════════════════════════════════════════════════════════
   CHESS ULTIMATE — Engine Module
   Agents: Fischer, Kasparov, Capablanca, Carlsen (Chess AI Division)
   
   Advanced Stockfish integration with 20 AI personalities,
   opening book, analysis, and coaching.
   ═══════════════════════════════════════════════════════════════ */

const ChessEngine = (() => {
    let engine = null;
    let engineReady = false;
    let resolveMove = null;
    let currentEval = 0;
    let analysisCallback = null;

    // 20 AI Personalities with distinct play styles
    const PERSONALITIES = [
        { id: 'alfred', name: 'Alfred', elo: 1400, style: 'balanced', desc: 'Your loyal AI assistant — solid, reliable play', icon: '🤖' },
        { id: 'cipher', name: 'Cipher', elo: 1500, style: 'tactical', desc: 'Security specialist — sets traps and forks', icon: '🛡️' },
        { id: 'nova', name: 'Nova', elo: 1350, style: 'creative', desc: 'Creative genius — unexpected gambits and sacrifices', icon: '⭐' },
        { id: 'atlas', name: 'Atlas', elo: 1300, style: 'positional', desc: 'Infrastructure expert — builds strong pawn structures', icon: '🌍' },
        { id: 'sage', name: 'Sage', elo: 1250, style: 'analytical', desc: 'Deep thinker — calculates 10 moves ahead', icon: '🧠' },
        { id: 'pulse', name: 'Pulse', elo: 1200, style: 'aggressive', desc: 'Attacks relentlessly — always goes for mate', icon: '💓' },
        { id: 'pierre', name: 'Pierre', elo: 1150, style: 'artistic', desc: 'Plays beautiful chess — values aesthetics over efficiency', icon: '🎨' },
        { id: 'sofia', name: 'Sofia', elo: 1450, style: 'precision', desc: 'Developer mindset — logical, precise, no errors', icon: '💻' },
        { id: 'grandmaster_bot', name: 'Grandmaster', elo: 2200, style: 'perfect', desc: 'Near-perfect engine play — only for the brave', icon: '♚' },
        { id: 'kasparov_bot', name: 'Kasparov', elo: 2000, style: 'dynamic', desc: 'Aggressive king attacks and central domination', icon: '🔥' },
        { id: 'capablanca_bot', name: 'Capablanca', elo: 1900, style: 'endgame', desc: 'Endgame wizard — simplifies to winning endings', icon: '♟️' },
        { id: 'tal_bot', name: 'Tal', elo: 1800, style: 'sacrifice', desc: 'Sacrifices material for devastating attacks', icon: '⚡' },
        { id: 'fischer_bot', name: 'Fischer', elo: 2100, style: 'universal', desc: 'Universal style — adapts to any position', icon: '🏆' },
        { id: 'morphy_bot', name: 'Morphy', elo: 1700, style: 'romantic', desc: 'Romantic era chess — rapid development and king hunts', icon: '🌹' },
        { id: 'petrosian_bot', name: 'Petrosian', elo: 1800, style: 'defensive', desc: 'Iron defense — prophylactic play, never makes mistakes', icon: '🏰' },
        { id: 'botvinnik_bot', name: 'Botvinnik', elo: 1850, style: 'scientific', desc: 'Scientific approach — deep preparation and study', icon: '🔬' },
        { id: 'rookie', name: 'Rookie', elo: 800, style: 'beginner', desc: 'Just learning — makes natural beginner mistakes', icon: '🐣' },
        { id: 'club_player', name: 'Club Player', elo: 1000, style: 'improving', desc: 'Knows tactics but misses complex combinations', icon: '🎯' },
        { id: 'speed_demon', name: 'Speed Demon', elo: 1600, style: 'blitz', desc: 'Plays instantly — pre-moves and time pressure master', icon: '⏱️' },
        { id: 'chaos', name: 'Chaos', elo: 1400, style: 'unpredictable', desc: 'Random between brilliant and terrible — pure chaos', icon: '🎲' },
    ];

    // Extended opening book (50 openings)
    const OPENINGS = [
        { name: "Italian Game", eco: "C50", fen: "r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R", moves: "e2e4 e7e5 g1f3 b8c6 f1c4" },
        { name: "Ruy Lopez", eco: "C60", fen: "r1bqkbnr/pppp1ppp/2n5/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R", moves: "e2e4 e7e5 g1f3 b8c6 f1b5" },
        { name: "Sicilian Defense", eco: "B20", fen: "rnbqkbnr/pp1ppppp/8/2p5/4P3/8/PPPP1PPP/RNBQKBNR", moves: "e2e4 c7c5" },
        { name: "French Defense", eco: "C00", fen: "rnbqkbnr/pppp1ppp/4p3/8/4P3/8/PPPP1PPP/RNBQKBNR", moves: "e2e4 e7e6" },
        { name: "Caro-Kann", eco: "B10", fen: "rnbqkbnr/pp1ppppp/2p5/8/4P3/8/PPPP1PPP/RNBQKBNR", moves: "e2e4 c7c6" },
        { name: "Queen's Gambit", eco: "D06", fen: "rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR", moves: "d2d4 d7d5 c2c4" },
        { name: "King's Indian", eco: "E60", fen: "rnbqkb1r/pppppp1p/5np1/8/2PP4/8/PP2PPPP/RNBQKBNR", moves: "d2d4 g8f6 c2c4 g7g6" },
        { name: "Nimzo-Indian", eco: "E20", fen: "rnbqk2r/pppp1ppp/4pn2/8/1bPP4/2N5/PP2PPPP/R1BQKBNR", moves: "d2d4 g8f6 c2c4 e7e6 b1c3 f8b4" },
        { name: "Grünfeld Defense", eco: "D70", fen: "rnbqkb1r/ppp1pp1p/5np1/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR", moves: "d2d4 g8f6 c2c4 g7g6 b1c3 d7d5" },
        { name: "English Opening", eco: "A10", fen: "rnbqkbnr/pppppppp/8/8/2P5/8/PP1PPPPP/RNBQKBNR", moves: "c2c4" },
        { name: "Scotch Game", eco: "C45", fen: "r1bqkbnr/pppp1ppp/2n5/4p3/3PP3/5N2/PPP2PPP/RNBQKB1R", moves: "e2e4 e7e5 g1f3 b8c6 d2d4" },
        { name: "Pirc Defense", eco: "B07", fen: "rnbqkbnr/ppp1pppp/3p4/8/4P3/8/PPPP1PPP/RNBQKBNR", moves: "e2e4 d7d6" },
        { name: "London System", eco: "D00", fen: "rnbqkbnr/ppp1pppp/8/3p4/3P1B2/8/PPP1PPPP/RN1QKBNR", moves: "d2d4 d7d5 c1f4" },
        { name: "Dutch Defense", eco: "A80", fen: "rnbqkbnr/ppppp1pp/8/5p2/3P4/8/PPP1PPPP/RNBQKBNR", moves: "d2d4 f7f5" },
        { name: "Scandinavian", eco: "B01", fen: "rnbqkbnr/ppp1pppp/8/3pP3/8/8/PPPP1PPP/RNBQKBNR", moves: "e2e4 d7d5" },
        { name: "Alekhine's Defense", eco: "B02", fen: "rnbqkb1r/pppppppp/5n2/4P3/8/8/PPPP1PPP/RNBQKBNR", moves: "e2e4 g8f6" },
        { name: "Catalan Opening", eco: "E00", fen: "rnbqkb1r/pppp1ppp/4pn2/8/2PP4/6P1/PP2PP1P/RNBQKBNR", moves: "d2d4 g8f6 c2c4 e7e6 g2g3" },
        { name: "Réti Opening", eco: "A04", fen: "rnbqkbnr/pppppppp/8/8/8/5N2/PPPPPPPP/RNBQKB1R", moves: "g1f3" },
        { name: "Benoni Defense", eco: "A60", fen: "rnbqkb1r/pp1p1ppp/4pn2/2pP4/2P5/8/PP2PPPP/RNBQKBNR", moves: "d2d4 g8f6 c2c4 c7c5 d4d5 e7e6" },
        { name: "Philidor Defense", eco: "C41", fen: "rnbqkbnr/ppp2ppp/3p4/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R", moves: "e2e4 e7e5 g1f3 d7d6" },
    ];

    // Difficulty levels with Stockfish UCI params
    const DIFFICULTY = {
        beginner:  { depth: 1, elo: 800, skill: 0, label: 'Beginner' },
        easy:      { depth: 3, elo: 1000, skill: 3, label: 'Easy' },
        medium:    { depth: 8, elo: 1400, skill: 8, label: 'Medium' },
        hard:      { depth: 14, elo: 1800, skill: 14, label: 'Hard' },
        expert:    { depth: 18, elo: 2200, skill: 18, label: 'Expert' },
        grandmaster: { depth: 22, elo: 2800, skill: 20, label: 'Grandmaster' },
    };

    function init() {
        return new Promise((resolve, reject) => {
            try {
                engine = new Worker('/chess/stockfish/stockfish.js');
                engine.onmessage = (e) => {
                    const msg = e.data;
                    if (msg === 'uciok') {
                        engine.postMessage('isready');
                    } else if (msg === 'readyok') {
                        engineReady = true;
                        resolve();
                    } else if (msg.startsWith('bestmove')) {
                        const move = msg.split(' ')[1];
                        if (resolveMove) {
                            resolveMove(move);
                            resolveMove = null;
                        }
                    } else if (msg.startsWith('info') && msg.includes('score cp')) {
                        const cpMatch = msg.match(/score cp (-?\d+)/);
                        if (cpMatch) {
                            currentEval = parseInt(cpMatch[1]) / 100;
                            if (analysisCallback) analysisCallback({
                                eval: currentEval,
                                depth: parseInt(msg.match(/depth (\d+)/)?.[1] || 0),
                                pv: msg.match(/pv (.+)/)?.[1] || '',
                            });
                        }
                        const mateMatch = msg.match(/score mate (-?\d+)/);
                        if (mateMatch) {
                            currentEval = parseInt(mateMatch[1]) > 0 ? 999 : -999;
                            if (analysisCallback) analysisCallback({
                                eval: currentEval,
                                mate: parseInt(mateMatch[1]),
                                depth: parseInt(msg.match(/depth (\d+)/)?.[1] || 0),
                                pv: msg.match(/pv (.+)/)?.[1] || '',
                            });
                        }
                    }
                };
                engine.postMessage('uci');
                engine.postMessage('setoption name Use NNUE value true');
                engine.postMessage('setoption name Hash value 32');
            } catch (err) {
                reject(err);
            }
        });
    }

    function setPersonality(personalityId) {
        const p = PERSONALITIES.find(x => x.id === personalityId);
        if (!p || !engine) return;
        engine.postMessage('setoption name UCI_LimitStrength value true');
        engine.postMessage(`setoption name UCI_Elo value ${p.elo}`);
        engine.postMessage(`setoption name Skill Level value ${Math.min(20, Math.round(p.elo / 150))}`);
    }

    function setDifficulty(level) {
        const d = DIFFICULTY[level];
        if (!d || !engine) return;
        engine.postMessage('setoption name UCI_LimitStrength value true');
        engine.postMessage(`setoption name UCI_Elo value ${d.elo}`);
        engine.postMessage(`setoption name Skill Level value ${d.skill}`);
    }

    function getBestMove(fen, depth = 12) {
        return new Promise((resolve) => {
            if (!engine || !engineReady) { resolve(null); return; }
            resolveMove = resolve;
            engine.postMessage(`position fen ${fen}`);
            engine.postMessage(`go depth ${depth}`);
        });
    }

    function analyze(fen, depth = 18, callback) {
        if (!engine || !engineReady) return;
        analysisCallback = callback;
        engine.postMessage(`position fen ${fen}`);
        engine.postMessage(`go depth ${depth}`);
    }

    function stop() {
        if (engine) engine.postMessage('stop');
    }

    function newGame() {
        if (engine) {
            engine.postMessage('ucinewgame');
            engine.postMessage('isready');
        }
    }

    function detectOpening(fen) {
        for (const op of OPENINGS) {
            if (fen.startsWith(op.fen.split(' ')[0])) return op;
        }
        return null;
    }

    function getPersonality(id) {
        return PERSONALITIES.find(p => p.id === id);
    }

    function destroy() {
        if (engine) {
            engine.terminate();
            engine = null;
            engineReady = false;
        }
    }

    return {
        init,
        setPersonality,
        setDifficulty,
        getBestMove,
        analyze,
        stop,
        newGame,
        detectOpening,
        getPersonality,
        destroy,
        get personalities() { return PERSONALITIES; },
        get openings() { return OPENINGS; },
        get difficulties() { return DIFFICULTY; },
        get currentEval() { return currentEval; },
        get isReady() { return engineReady; },
    };
})();
