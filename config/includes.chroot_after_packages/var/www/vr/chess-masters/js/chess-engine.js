/* ═══════════════════════════════════════════════════════════════
   CHESS MASTERS — Engine + Alfred AI Module
   GSM Alfred OS · Project Grandmaster II
   
   Dual-brain chess intelligence:
   - Stockfish 16 NNUE for raw calculation
   - Alfred OS Agent Runtime for personality, commentary,
     reasoning, teaching, and adaptive play
   - 20 AI personalities with distinct strategies
   - Opening book, analysis, coaching
   - Agent commentary system (explains moves in character)
   ═══════════════════════════════════════════════════════════════ */

const ChessEngine = (() => {
    'use strict';

    let engine = null;
    let engineReady = false;
    let resolveMove = null;
    let currentEval = 0;
    let analysisCallback = null;
    let currentPersonality = null;

    // ── 20 AI Personalities — each with distinct play style ──
    const PERSONALITIES = [
        { id: 'alfred', name: 'Alfred', elo: 1400, style: 'balanced', desc: 'Your trusted butler — solid, reliable, always composed', icon: '🎩', voice: 'refined' },
        { id: 'cipher', name: 'Cipher', elo: 1500, style: 'tactical', desc: 'Security specialist — sees traps three moves ahead', icon: '🛡️', voice: 'sharp' },
        { id: 'nova', name: 'Nova', elo: 1350, style: 'creative', desc: 'Creative genius — gambits, sacrifices, beautiful play', icon: '⭐', voice: 'excited' },
        { id: 'atlas', name: 'Atlas', elo: 1300, style: 'positional', desc: 'Infrastructure expert — impregnable pawn structures', icon: '🌍', voice: 'steady' },
        { id: 'sage', name: 'Sage', elo: 1250, style: 'analytical', desc: 'Deep thinker — calculates every variation', icon: '🧠', voice: 'thoughtful' },
        { id: 'pulse', name: 'Pulse', elo: 1200, style: 'aggressive', desc: 'Attacks relentlessly — always hunting the king', icon: '💓', voice: 'intense' },
        { id: 'pierre', name: 'Pierre', elo: 1150, style: 'artistic', desc: 'Values beauty over efficiency — plays for aesthetics', icon: '🎨', voice: 'poetic' },
        { id: 'sofia', name: 'Sofia', elo: 1450, style: 'precision', desc: 'Developer mindset — logical, precise, zero errors', icon: '💻', voice: 'clinical' },
        { id: 'grandmaster_bot', name: 'Grandmaster', elo: 2200, style: 'perfect', desc: 'Near-perfect engine play — only for the brave', icon: '♚', voice: 'commanding' },
        { id: 'kasparov_bot', name: 'Kasparov', elo: 2000, style: 'dynamic', desc: 'Aggressive king attacks and central domination', icon: '🔥', voice: 'fierce' },
        { id: 'capablanca_bot', name: 'Capablanca', elo: 1900, style: 'endgame', desc: 'Endgame wizard — simplifies to winning endings', icon: '♟️', voice: 'calm' },
        { id: 'tal_bot', name: 'Tal', elo: 1800, style: 'sacrifice', desc: 'Sacrifices material for devastating attacks', icon: '⚡', voice: 'daring' },
        { id: 'fischer_bot', name: 'Fischer', elo: 2100, style: 'universal', desc: 'Universal style — adapts to any position', icon: '🏆', voice: 'confident' },
        { id: 'morphy_bot', name: 'Morphy', elo: 1700, style: 'romantic', desc: 'Romantic era chess — rapid development, king hunts', icon: '🌹', voice: 'elegant' },
        { id: 'petrosian_bot', name: 'Petrosian', elo: 1800, style: 'defensive', desc: 'Iron defense — prophylactic play, never blunders', icon: '🏰', voice: 'patient' },
        { id: 'botvinnik_bot', name: 'Botvinnik', elo: 1850, style: 'scientific', desc: 'Scientific approach — deep preparation', icon: '🔬', voice: 'methodical' },
        { id: 'rookie', name: 'Rookie', elo: 800, style: 'beginner', desc: 'Just learning — makes natural beginner mistakes', icon: '🐣', voice: 'eager' },
        { id: 'club_player', name: 'Club Player', elo: 1000, style: 'improving', desc: 'Knows tactics but misses deep combinations', icon: '🎯', voice: 'friendly' },
        { id: 'speed_demon', name: 'Speed Demon', elo: 1600, style: 'blitz', desc: 'Plays instantly — time pressure master', icon: '⏱️', voice: 'rapid' },
        { id: 'chaos', name: 'Chaos', elo: 1400, style: 'unpredictable', desc: 'Randomly brilliant or terrible — pure chaos', icon: '🎲', voice: 'wild' },
    ];

    // ── Opening Book (20 openings) ──
    const OPENINGS = [
        { name: "Italian Game", eco: "C50", moves: "e2e4 e7e5 g1f3 b8c6 f1c4" },
        { name: "Ruy Lopez", eco: "C60", moves: "e2e4 e7e5 g1f3 b8c6 f1b5" },
        { name: "Sicilian Defense", eco: "B20", moves: "e2e4 c7c5" },
        { name: "French Defense", eco: "C00", moves: "e2e4 e7e6" },
        { name: "Caro-Kann", eco: "B10", moves: "e2e4 c7c6" },
        { name: "Queen's Gambit", eco: "D06", moves: "d2d4 d7d5 c2c4" },
        { name: "King's Indian", eco: "E60", moves: "d2d4 g8f6 c2c4 g7g6" },
        { name: "Nimzo-Indian", eco: "E20", moves: "d2d4 g8f6 c2c4 e7e6 b1c3 f8b4" },
        { name: "London System", eco: "D00", moves: "d2d4 d7d5 c1f4" },
        { name: "Scotch Game", eco: "C45", moves: "e2e4 e7e5 g1f3 b8c6 d2d4" },
        { name: "Catalan Opening", eco: "E00", moves: "d2d4 g8f6 c2c4 e7e6 g2g3" },
        { name: "English Opening", eco: "A10", moves: "c2c4" },
        { name: "Dutch Defense", eco: "A80", moves: "d2d4 f7f5" },
        { name: "Pirc Defense", eco: "B07", moves: "e2e4 d7d6" },
        { name: "Scandinavian", eco: "B01", moves: "e2e4 d7d5" },
        { name: "Alekhine's Defense", eco: "B02", moves: "e2e4 g8f6" },
        { name: "Réti Opening", eco: "A04", moves: "g1f3" },
        { name: "Benoni Defense", eco: "A60", moves: "d2d4 g8f6 c2c4 c7c5 d4d5 e7e6" },
        { name: "Philidor Defense", eco: "C41", moves: "e2e4 e7e5 g1f3 d7d6" },
        { name: "Grünfeld Defense", eco: "D70", moves: "d2d4 g8f6 c2c4 g7g6 b1c3 d7d5" },
    ];

    // ── Difficulty Levels ──
    const DIFFICULTY = {
        beginner:     { depth: 1, elo: 800, skill: 0, label: 'Beginner' },
        easy:         { depth: 3, elo: 1000, skill: 3, label: 'Easy' },
        medium:       { depth: 8, elo: 1400, skill: 8, label: 'Medium' },
        hard:         { depth: 14, elo: 1800, skill: 14, label: 'Hard' },
        expert:       { depth: 18, elo: 2200, skill: 18, label: 'Expert' },
        grandmaster:  { depth: 22, elo: 2800, skill: 20, label: 'Grandmaster' },
    };

    // ── Commentary Templates (by style) ──
    const COMMENTARY = {
        balanced: {
            move:    ["A solid developing move.", "Building a harmonious position.", "Maintaining balance on the board."],
            capture: ["A necessary exchange.", "Simplifying towards a favorable position.", "Equal trades benefit the side with initiative."],
            check:   ["Your king is exposed — be careful.", "Check! Time to find safety.", "The noose tightens."],
            blunder: ["Hmm, there may have been something better there.", "That's an interesting choice...", "I sense an opportunity."],
        },
        tactical: {
            move:    ["Setting up a tactical sequence.", "Every piece aims at a target.", "The pattern is forming."],
            capture: ["Tactical strike! Material advantage.", "The combination unfolds.", "Calculated destruction."],
            check:   ["Check — and the threats multiply.", "Your king can't hide forever.", "The net closes in."],
            blunder: ["You've left a weakness. I see it.", "Critical error detected.", "Opportunity identified and catalogued."],
        },
        creative: {
            move:    ["Poetry in motion!", "An unconventional but inspired idea.", "Who says chess can't be art?"],
            capture: ["A bold sacrifice for the greater canvas!", "Removing the obstacle to beauty!", "The masterpiece takes shape."],
            check:   ["Check! The crescendo builds!", "Your king dances in the spotlight!", "A dramatic twist in our story!"],
            blunder: ["Ooh, was that intentional? Because it's working for me!", "Thank you for the creative opportunity!", "Improvisation!"],
        },
        aggressive: {
            move:    ["Advancing towards your king.", "The attack doesn't stop.", "Pressure. Relentless pressure."],
            capture: ["Destroyed.", "One less defender.", "Your army crumbles."],
            check:   ["CHECK! Nowhere to run.", "Your king trembles.", "The hunt is on."],
            blunder: ["You blinked. Fatal mistake.", "Opening your defenses? Thank you.", "Now I strike."],
        },
        defensive: {
            move:    ["Strengthening my fortress.", "Patience is the ultimate weapon.", "Every piece is perfectly placed."],
            capture: ["Removing a potential threat.", "Preventive medicine.", "The wall grows stronger."],
            check:   ["An unexpected sortie. Careful.", "Even the fortress can project power.", "A reminder that defense can attack."],
            blunder: ["An overextension, I think.", "You've left your guard down.", "My patience is rewarded."],
        },
        beginner: {
            move:    ["I hope this is good!", "Hmm, what does this piece do again?", "I think I read about this move once!"],
            capture: ["Oh cool, I can take that!", "Yay, free piece!", "Wait, is this a trap? Whatever, taking it!"],
            check:   ["Check! Did I do that right?", "Wow, I found a check!", "Is that checkmate? No? Oh well."],
            blunder: ["Oops, hope you don't notice that...", "Wait, I think I just... never mind.", "Chess is hard, okay?"],
        },
        positional: {
            move:    ["Improving piece placement.", "Space advantage is everything.", "Control the key squares first."],
            capture: ["Exchanging to improve my pawn structure.", "A strategically motivated trade.", "Simplifying in my favor."],
            check:   ["Check — but the position matters more than the threat.", "A positional check with lasting effect.", "Your king is misplaced now."],
            blunder: ["A structural weakness appears.", "Your pawn islands will haunt you.", "The long-term damage is done."],
        },
        analytical: {
            move:    ["The calculation confirms this is optimal.", "Seventeen variations checked. This is the one.", "Proceeding along the principal variation."],
            capture: ["Expected value: positive. Executing.", "The exchange favors us by 0.3 pawns.", "Materially justified."],
            check:   ["Check. Your response space narrows to two options.", "Forced sequence initiated.", "Calculating all king flights."],
            blunder: ["My analysis detects an error in your logic.", "Suboptimal. I've catalogued three refutations.", "A deviation from best play."],
        },
        artistic: {
            move:    ["Like a brushstroke on the board.", "Beauty emerges from every well-placed piece.", "Chess is poetry in motion."],
            capture: ["A necessary sacrifice for the composition.", "The canvas demands this exchange.", "Removing the dissonant note."],
            check:   ["Check — a dramatic turn in our masterpiece!", "The composition reaches its climax!", "Your king walks through my gallery."],
            blunder: ["You've disrupted the harmony. I shall compose around it.", "An imperfection I can work with.", "The muse speaks through your error."],
        },
        precision: {
            move:    ["Executed as planned.", "Zero deviation from optimal.", "Clean. Efficient. Correct."],
            capture: ["Trade executed. Net positive.", "Removing a variable from the equation.", "Calculated trade. Proceeding."],
            check:   ["Check. Response required.", "King safety compromised. Adjust.", "Precise attack vector identified."],
            blunder: ["Error logged.", "Evaluating exploit potential.", "Your code has a bug."],
        },
        perfect: {
            move:    ["The position demands nothing less.", "Stockfish agrees.", "There is only one correct move."],
            capture: ["Inevitable.", "The exchange was predetermined.", "Material dictates the outcome."],
            check:   ["Check. The end approaches.", "Your king has no adequate defense.", "Resistance is theoretical."],
            blunder: ["This is the beginning of the end.", "No recovery is possible.", "I calculate mate in twelve."],
        },
        dynamic: {
            move:    ["Seizing the initiative!", "Dynamic play wins games!", "Activity over material — always!"],
            capture: ["Aggressive exchange! Keep the pressure!", "Opening lines for the attack!", "Taking control of the position!"],
            check:   ["CHECK! The attack doesn't relent!", "Your king is in the crossfire!", "No time to regroup!"],
            blunder: ["Now the counterattack begins!", "You've given me the initiative!", "Dynamic energy shifts my way!"],
        },
        endgame: {
            move:    ["The endgame is where games are truly won.", "King activity becomes paramount now.", "Each pawn is worth a piece here."],
            capture: ["Simplifying towards a winning ending.", "One step closer to the pure endgame.", "The fewer pieces, the clearer the path."],
            check:   ["Activating my king with tempo.", "Check — and my king marches forward.", "The endgame check is a powerful tool."],
            blunder: ["A critical endgame error.", "In the endgame, there is no margin.", "That pawn move cannot be taken back."],
        },
        sacrifice: {
            move:    ["Setting the stage for something beautiful.", "The quiet move before the storm.", "Can you feel it coming?"],
            capture: ["Take my piece. I dare you.", "A sacrifice that echoes through time!", "Material is temporary. Initiative is eternal!"],
            check:   ["CHECK! Was it worth the sacrifice?", "The sacrificial fire burns bright!", "Your king flees from ghosts!"],
            blunder: ["You've accepted the wrong gift.", "Now you see the point of the sacrifice.", "The refutation was always there."],
        },
        universal: {
            move:    ["Adapting to the position's needs.", "Every position requires a unique approach.", "Flexibility is the hallmark of mastery."],
            capture: ["The position called for action.", "Adjusting the material balance.", "Playing what the position demands."],
            check:   ["Check — the right tool for this moment.", "Applying pressure where it's needed.", "A timely intervention."],
            blunder: ["I see the weakness in your approach.", "Universal understanding reveals the flaw.", "No style can save you here."],
        },
        romantic: {
            move:    ["Rapid development! The pieces yearn for battle!", "Forward! To glory!", "The game begins in earnest!"],
            capture: ["En garde! Your piece falls!", "The romantic style demands action!", "A dashing strike!"],
            check:   ["Check! The king hunt commences!", "Your majesty, you are not safe!", "Cavalry charge!"],
            blunder: ["A gift! How generous of you!", "The romantic in me rejoices!", "This calls for an even bolder attack!"],
        },
        scientific: {
            move:    ["Hypothesis confirmed by the position.", "Methodical improvement.", "Following established theory."],
            capture: ["Data supports this exchange.", "Removing the variable.", "Empirically sound."],
            check:   ["The experiment yields a check.", "Observable: king in danger.", "Testing your defensive preparation."],
            blunder: ["Your hypothesis was incorrect.", "The data does not support your last move.", "A failure to prepare properly."],
        },
        improving: {
            move:    ["I think that's a good move... right?", "Getting better every game!", "I studied this position!"],
            capture: ["Nice, I saw that tactic!", "Is this a fork? I think it's a fork!", "My tactics trainer prepared me for this!"],
            check:   ["Check! My calculation was right!", "I actually saw this coming!", "Getting more confident now!"],
            blunder: ["Hmm, that looks tricky. Let me think...", "Oh! I see what you did there.", "That's a new pattern for me to study."],
        },
        blitz: {
            move:    ["Bam!", "Next.", "Moving instantly — pressure is everything."],
            capture: ["Gone.", "Took it — no time to explain.", "Fast hands, fast mind."],
            check:   ["Check! Clock ticking!", "Speed kills!", "Tempo tempo tempo!"],
            blunder: ["You're running out of time and moves.", "Too slow!", "The clock is your real opponent."],
        },
        unpredictable: {
            move:    ["Even I don't know why I played that.", "Chaos is a ladder!", "Random? Or genius? You decide."],
            capture: ["Took it because... why not?", "SURPRISE!", "Nobody expects that!"],
            check:   ["Plot twist: CHECK!", "Didn't see that coming, did you?", "Chaotic check energy!"],
            blunder: ["Thank you for adding to my chaos!", "Was that intentional? Doesn't matter!", "In chaos, all moves are equal!"],
        },
    };

    // ═══ STOCKFISH INIT ═══
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
        currentPersonality = p;
        engine.postMessage('setoption name UCI_LimitStrength value true');
        engine.postMessage(`setoption name UCI_Elo value ${p.elo}`);
        // Map ELO 800-2800 to Skill 0-20
        const skill = Math.min(20, Math.max(0, Math.round((p.elo - 800) / 100)));
        engine.postMessage(`setoption name Skill Level value ${skill}`);
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

    function stop() { if (engine) engine.postMessage('stop'); }

    function newGame() {
        if (engine) {
            engine.postMessage('ucinewgame');
            engine.postMessage('isready');
        }
    }

    // Move history tracking for opening detection
    let moveHistory = [];

    // ═══ ENHANCED COMMENTARY SYSTEM ═══
    // Integrates with ChessOpenings and ChessClassics when available

    function getCommentary(moveType) {
        if (!currentPersonality) return null;

        let styleKey = currentPersonality.style;
        const commentaryMap = COMMENTARY[styleKey] || COMMENTARY.balanced;
        const options = commentaryMap[moveType] || commentaryMap.move;

        if (!options || options.length === 0) return null;
        return {
            text: options[Math.floor(Math.random() * options.length)],
            personality: currentPersonality,
        };
    }

    function getMoveCommentary(move, game) {
        if (!currentPersonality) return null;

        let type = 'move';
        if (move.captured) type = 'capture';
        if (game.in_check()) type = 'check';

        const base = getCommentary(type);
        if (!base) return null;

        // Enrich with opening theory if ChessOpenings is available
        const openingInfo = detectOpening();
        if (openingInfo && openingInfo.name) {
            base.opening = openingInfo.name;
            // On opening moves, sometimes add opening info
            if (moveHistory.length <= 20 && Math.random() > 0.5) {
                base.text += ` We're in the ${openingInfo.name}.`;
                if (openingInfo.description) {
                    base.theory = openingInfo.description;
                }
            }
        }

        // Check if the position resembles a famous game
        const classicRef = matchClassicGame(game);
        if (classicRef) {
            base.classicRef = classicRef;
            if (Math.random() > 0.4) {
                base.text += ` This reminds me of ${classicRef.white} vs ${classicRef.black}, ${classicRef.year}.`;
            }
        }

        return base;
    }

    // ═══ ENHANCED OPENING DETECTION ═══
    // Uses ChessOpenings trie when available, falls back to basic detection

    function trackMove(uciMove) {
        moveHistory.push(uciMove);
    }

    function resetMoveHistory() {
        moveHistory = [];
    }

    function detectOpening() {
        // Use the comprehensive opening book if available
        if (typeof ChessOpenings !== 'undefined') {
            const result = ChessOpenings.lookup(moveHistory);
            if (result) return result;
        }
        // Fallback to basic detection
        const moveStr = moveHistory.join(' ');
        for (const op of OPENINGS) {
            if (moveStr.startsWith(op.moves) || op.moves.startsWith(moveStr)) {
                return op;
            }
        }
        return null;
    }

    // Get an opening book move for the AI (personality-aware)
    function getBookMove(game) {
        if (typeof ChessOpenings === 'undefined') return null;
        if (moveHistory.length > 24) return null; // Past opening phase

        // Try personality-specific opening first
        if (currentPersonality && moveHistory.length <= 2) {
            const selected = ChessOpenings.selectOpeningForAI(
                currentPersonality.id,
                moveHistory.length % 2 === 0 // is white
            );
            if (selected && selected.uciMoves) {
                const nextMove = selected.uciMoves[moveHistory.length];
                if (nextMove && isLegalMove(nextMove, game)) return nextMove;
            }
        }

        // Otherwise get any book move for this position
        const bookMoves = ChessOpenings.getBookMoves(moveHistory);
        if (bookMoves && bookMoves.length > 0) {
            // Pick a random book move weighted by personality preference
            const pick = bookMoves[Math.floor(Math.random() * bookMoves.length)];
            if (isLegalMove(pick, game)) return pick;
        }
        return null;
    }

    function isLegalMove(uciMove, game) {
        const from = uciMove.substring(0, 2);
        const to = uciMove.substring(2, 4);
        const promo = uciMove[4] || undefined;
        const legal = game.moves({ verbose: true });
        return legal.some(m => m.from === from && m.to === to &&
            (!promo || m.promotion === promo));
    }

    // ═══ FAMOUS GAME MATCHING ═══
    // Compare current position to classic games for commentary enrichment

    function matchClassicGame(game) {
        if (typeof ChessClassics === 'undefined') return null;
        // Match by opening + move count range
        const opening = detectOpening();
        if (!opening) return null;
        const allGames = ChessClassics.getAll();
        const candidates = allGames.filter(g =>
            g.opening && opening.name &&
            g.opening.toLowerCase().includes(opening.name.toLowerCase().split(' ')[0])
        );
        if (candidates.length > 0) {
            return candidates[Math.floor(Math.random() * candidates.length)];
        }
        return null;
    }

    // ═══ ENHANCED AI MOVE ═══
    // Tries book move first, then Stockfish

    function getAIMove(fen, depth, game) {
        // Phase 1: Try opening book
        const bookMove = getBookMove(game);
        if (bookMove) {
            return Promise.resolve(bookMove);
        }
        // Phase 2: Stockfish
        return getBestMove(fen, depth);
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
        moveHistory = [];
    }

    return {
        init,
        setPersonality,
        setDifficulty,
        getBestMove,
        getAIMove,
        analyze,
        stop,
        newGame,
        trackMove,
        resetMoveHistory,
        detectOpening,
        getBookMove,
        matchClassicGame,
        getPersonality,
        getCommentary,
        getMoveCommentary,
        destroy,
        get personalities() { return PERSONALITIES; },
        get openings() { return OPENINGS; },
        get difficulties() { return DIFFICULTY; },
        get currentEval() { return currentEval; },
        get isReady() { return engineReady; },
        get activePersonality() { return currentPersonality; },
        get history() { return moveHistory; },
    };
})();
