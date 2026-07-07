/* ═══════════════════════════════════════════════════════════════════════════════
   CHESS VOICE COMMANDER — Alfred Integration
   Full voice control for VR Chess Arena
   Powered by Web Speech API + Alfred AI Widget
   ═══════════════════════════════════════════════════════════════════════════════ */
(function() {
'use strict';

const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
if (!SpeechRec) {
    console.warn('[ChessVoice] Web Speech API not supported in this browser.');
    return;
}

/* ── State ── */
let listening = false;
let recognition = null;
let continuous = false;
let micBtn = null;
let voiceIndicator = null;
let lastAnnouncement = '';
let handsFreeMode = false;
let wakeWordActive = false;  // true when wake word detected, waiting for command

/* ── Piece name maps ── */
const PIECE_NAMES = {
    'pawn': 'p', 'pond': 'p', 'pон': 'p',
    'rook': 'r', 'castle': 'r', 'tower': 'r',
    'knight': 'n', 'horse': 'n', 'night': 'n',
    'bishop': 'b', 'diagonal': 'b',
    'queen': 'q',
    'king': 'k',
};

const PIECE_SYMBOLS = { 'p': '♟', 'r': '♜', 'n': '♞', 'b': '♝', 'q': '♛', 'k': '♚' };

/* ── Column name maps (spoken → file letter) ── */
const COLUMN_NAMES = {
    'a': 'a', 'alpha': 'a', 'alfa': 'a', 'ay': 'a', 'eh': 'a',
    'b': 'b', 'bravo': 'b', 'bee': 'b', 'be': 'b',
    'c': 'c', 'charlie': 'c', 'see': 'c', 'sea': 'c',
    'd': 'd', 'delta': 'd', 'dee': 'd',
    'e': 'e', 'echo': 'e', 'ee': 'e',
    'f': 'f', 'foxtrot': 'f', 'fox': 'f', 'ef': 'f',
    'g': 'g', 'golf': 'g', 'gee': 'g',
    'h': 'h', 'hotel': 'h', 'aitch': 'h', 'age': 'h',
};

/* ── Row name maps (spoken → rank) ── */
const ROW_NAMES = {
    '1': '1', 'one': '1', 'won': '1',
    '2': '2', 'two': '2', 'to': '2', 'too': '2', 'tu': '2',
    '3': '3', 'three': '3', 'tree': '3', 'free': '3',
    '4': '4', 'four': '4', 'for': '4', 'fore': '4',
    '5': '5', 'five': '5',
    '6': '6', 'six': '6', 'sic': '6', 'sex': '6',
    '7': '7', 'seven': '7',
    '8': '8', 'eight': '8', 'ate': '8',
};

/* ── Camera mode names ── */
const CAMERA_NAMES = {
    'orbit': 'orbit', 'orbital': 'orbit', 'rotating': 'orbit',
    'white': 'white-side', 'white side': 'white-side', 'whites': 'white-side',
    'black': 'black-side', 'black side': 'black-side', 'blacks': 'black-side',
    'top': 'top-down', 'top down': 'top-down', 'overhead': 'top-down', 'bird': 'top-down', 'birds eye': 'top-down', "bird's eye": 'top-down', 'above': 'top-down',
    'cinematic': 'cinematic', 'cinema': 'cinematic', 'movie': 'cinematic', 'dramatic': 'cinematic',
    'corner': 'corner', 'diagonal': 'corner', 'angle': 'corner',
    'shoulder': 'shoulder', 'over the shoulder': 'shoulder', 'follow': 'shoulder',
    'free': 'free-walk', 'walk': 'free-walk', 'first person': 'free-walk', 'fps': 'free-walk', 'free walk': 'free-walk',
};

/* ── Theme names ── */
const THEME_NAMES = {
    'obsidian': 'obsidian', 'dark': 'obsidian', 'default': 'obsidian', 'purple': 'obsidian',
    'classic': 'classic', 'wood': 'classic', 'wooden': 'classic', 'traditional': 'classic', 'brown': 'classic',
    'tournament': 'tournament', 'green': 'tournament', 'competition': 'tournament',
    'marble': 'marble', 'gold': 'marble', 'elegant': 'marble', 'white': 'marble', 'luxury': 'marble',
    'crystal': 'crystal', 'ice': 'crystal', 'frozen': 'crystal', 'blue': 'crystal', 'icy': 'crystal',
    'rosewood': 'rosewood', 'rose': 'rosewood', 'warm': 'rosewood', 'red': 'rosewood',
};

/* ── Piece style names ── */
const STYLE_NAMES = {
    'staunton': 'staunton', 'standard': 'staunton', 'classic': 'staunton', 'normal': 'staunton', 'default': 'staunton',
    'gothic': 'gothic', 'dark': 'gothic', 'metal': 'gothic', 'metallic': 'gothic',
    'glass': 'glass', 'crystal': 'glass', 'transparent': 'glass', 'clear': 'glass',
};

/* ── Difficulty mappings ── */
const DIFFICULTY_MAP = {
    'easy': 2, 'beginner': 2, 'simple': 2, 'casual': 2, 'noob': 2,
    'medium': 8, 'normal': 8, 'moderate': 8, 'intermediate': 8,
    'hard': 14, 'difficult': 14, 'tough': 14, 'challenging': 14, 'advanced': 14,
    'expert': 20, 'master': 20, 'grandmaster': 20, 'maximum': 20, 'max': 20, 'impossible': 20,
};

/* ═══════════════════════════════════════════════════════════════
   TOAST NOTIFICATION (chess-native)
   ═══════════════════════════════════════════════════════════════ */
function voiceToast(msg) {
    if (typeof showToast === 'function') {
        showToast('🎙️ ' + msg);
    }
}

/* ═══════════════════════════════════════════════════════════════
   SPEAK (TTS) — Alfred 'onyx' voice (same as toll-free line)
   Uses server-side TTS via /api/chess-tts.php with
   automatic fallback to browser speechSynthesis.
   ═══════════════════════════════════════════════════════════════ */
let alfredVoice = null;
let alfredVolume = 0.8;
let alfredAudio = null;  // current Audio element for server TTS
const ASSISTANT_SPEECH_STORAGE_KEY = 'vr-chess-assistant-speech-enabled';
let speechEnabled = false;

try {
    speechEnabled = localStorage.getItem(ASSISTANT_SPEECH_STORAGE_KEY) === '1';
} catch (e) {
    speechEnabled = false;
}

function persistSpeechEnabled() {
    try {
        localStorage.setItem(ASSISTANT_SPEECH_STORAGE_KEY, speechEnabled ? '1' : '0');
    } catch (e) {}
}

function findAlfredVoice() {
    if (alfredVoice) return alfredVoice;
    const voices = window.speechSynthesis.getVoices();
    if (!voices.length) return null;
    // Fallback: prefer deep male English voices
    const preferred = [
        'Microsoft David Desktop', 'Microsoft David', 'Microsoft Mark Desktop', 'Microsoft Mark',
        'Microsoft George Desktop', 'Microsoft George', 'Microsoft Richard',
        'Google UK English Male', 'Google US English',
        'Daniel', 'James', 'David', 'Alex', 'Fred',
        'en-GB-RyanNeural', 'en-US-GuyNeural', 'en-US-ChristopherNeural',
        'en-GB', 'en-US'
    ];
    for (const name of preferred) {
        const v = voices.find(v => v.name.includes(name) && v.lang.startsWith('en'));
        if (v) { alfredVoice = v; return v; }
    }
    alfredVoice = voices.find(v => v.lang.startsWith('en')) || voices[0];
    return alfredVoice;
}

// Preload browser voices (Chrome loads async)
if (window.speechSynthesis) {
    window.speechSynthesis.onvoiceschanged = () => { alfredVoice = null; findAlfredVoice(); };
    findAlfredVoice();
}

/* Server-side TTS via 'onyx' voice — falls back to browser speechSynthesis */
function speakServerTTS(text) {
    const url = '/api/chess-tts.php?text=' + encodeURIComponent(text);
    fetch(url).then(r => r.json()).then(data => {
        if (data.url) {
            if (alfredAudio) { alfredAudio.pause(); alfredAudio = null; }
            alfredAudio = new Audio(data.url);
            alfredAudio.volume = alfredVolume;
            alfredAudio.play().catch(() => speakBrowserTTS(text));
        } else {
            speakBrowserTTS(text);
        }
    }).catch(() => speakBrowserTTS(text));
}

/* Browser speechSynthesis fallback */
function speakBrowserTTS(text) {
    if (!window.speechSynthesis) return;
    const utt = new SpeechSynthesisUtterance(text);
    const voice = findAlfredVoice();
    if (voice) utt.voice = voice;
    utt.rate = 1.0;
    utt.pitch = 0.75;
    utt.volume = alfredVolume;
    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(utt);
}

function speak(text) {
    if (!speechEnabled) return;
    if (text === lastAnnouncement) return;
    lastAnnouncement = text;
    speakServerTTS(text);
}

function setAlfredVolume(val) {
    alfredVolume = Math.max(0, Math.min(1, val));
    if (alfredAudio) alfredAudio.volume = alfredVolume;
    const slider = document.getElementById('voiceVolumeSlider');
    if (slider) slider.value = Math.round(alfredVolume * 100);
}

/* ═══════════════════════════════════════════════════════════════
   PARSE MOVE FROM NATURAL LANGUAGE
   ═══════════════════════════════════════════════════════════════ */
function parseSquare(words) {
    // Try to parse a square from an array of words
    // e.g. ["e", "4"] → "e4", ["echo", "four"] → "e4"
    if (!words || words.length === 0) return null;

    // Single token like "e4"
    if (words.length === 1 && /^[a-h][1-8]$/.test(words[0])) {
        return words[0];
    }

    // Two tokens: file + rank
    if (words.length >= 2) {
        const file = COLUMN_NAMES[words[0]];
        const rank = ROW_NAMES[words[1]];
        if (file && rank) return file + rank;
    }

    // Single token that maps to file, look for rank in next
    if (words.length >= 2) {
        const file = COLUMN_NAMES[words[0]];
        if (file && /^[1-8]$/.test(words[1])) return file + words[1];
    }

    return null;
}

function parseMoveCommand(text) {
    const t = text.toLowerCase().replace(/[.,!?]/g, '').trim();
    const words = t.split(/\s+/);

    // ── Castling ──
    if (/castle\s*(?:king\s*side|short|king)/i.test(t) || /king\s*side\s*castle/i.test(t) || t === 'o-o' || t === '0-0') {
        return { type: 'castle', side: 'king' };
    }
    if (/castle\s*(?:queen\s*side|long|queen)/i.test(t) || /queen\s*side\s*castle/i.test(t) || t === 'o-o-o' || t === '0-0-0') {
        return { type: 'castle', side: 'queen' };
    }

    // ── Direct algebraic: "e4", "nf3", "qxd7" etc ──
    const algebraicMatch = t.match(/^([nbrqk])?([a-h])?x?([a-h][1-8])(?:\s*=?\s*([nbrq]))?(?:\s*[+#])?$/);
    if (algebraicMatch) {
        return { type: 'algebraic', san: t.replace(/\s/g, '') };
    }

    // ── "[piece] to [square]" or "move [piece] to [square]" ──
    const moveToMatch = t.match(/(?:move\s+)?(?:the\s+)?(\w+)\s+(?:to|on|at)\s+(\w+)\s*(\w*)/);
    if (moveToMatch) {
        const piece = PIECE_NAMES[moveToMatch[1]];
        const sq = parseSquare([moveToMatch[2], moveToMatch[3]]);
        if (sq) {
            return { type: 'piece-to', piece: piece || null, to: sq };
        }
    }

    // ── "[piece] [square]" — e.g. "knight f3", "pawn e4" ──
    const pieceSquareMatch = t.match(/(?:move\s+)?(?:the\s+)?(\w+)\s+([a-h])\s*([1-8])/);
    if (pieceSquareMatch) {
        const piece = PIECE_NAMES[pieceSquareMatch[1]];
        const sq = pieceSquareMatch[2] + pieceSquareMatch[3];
        if (piece && sq) {
            return { type: 'piece-to', piece, to: sq };
        }
    }

    // ── "[from] to [to]" — e.g. "e2 to e4" ──
    const fromToMatch = t.match(/(\w+)\s*(\w*)\s+(?:to|takes?)\s+(\w+)\s*(\w*)/);
    if (fromToMatch) {
        const from = parseSquare([fromToMatch[1], fromToMatch[2]].filter(Boolean));
        const to = parseSquare([fromToMatch[3], fromToMatch[4]].filter(Boolean));
        if (from && to) return { type: 'from-to', from, to };
    }

    // ── Just a square: "e4" (for pawn moves or second click) ──
    const justSquare = t.match(/^(\w+)\s*(\w*)$/);
    if (justSquare) {
        const sq = parseSquare([justSquare[1], justSquare[2]].filter(w => w));
        if (sq) return { type: 'square', to: sq };
    }

    return null;
}

/* ═══════════════════════════════════════════════════════════════
   EXECUTE MOVE
   ═══════════════════════════════════════════════════════════════ */
function executeVoiceMove(parsed) {
    if (typeof chess === 'undefined' || !chess) {
        voiceToast('No game in progress');
        return false;
    }

    const turn = chess.turn();
    const isHumanTurn = (typeof gameMode !== 'undefined') &&
        ((gameMode === 'play-white' && turn === 'w') ||
         (gameMode === 'play-black' && turn === 'b') ||
         gameMode === 'pvp');
    if (!isHumanTurn) {
        voiceToast("Not your turn");
        return false;
    }

    let move = null;

    if (parsed.type === 'castle') {
        const castleStr = parsed.side === 'king' ? 'O-O' : 'O-O-O';
        // chess.js alternative notations
        move = chess.move(castleStr) || chess.move(castleStr.toLowerCase());
        if (!move) {
            // Try explicit king move
            const rank = turn === 'w' ? '1' : '8';
            const from = 'e' + rank;
            const to = parsed.side === 'king' ? ('g' + rank) : ('c' + rank);
            move = chess.move({ from, to });
        }
    } else if (parsed.type === 'algebraic') {
        // Try direct SAN
        move = chess.move(parsed.san);
    } else if (parsed.type === 'from-to') {
        move = chess.move({ from: parsed.from, to: parsed.to, promotion: 'q' });
    } else if (parsed.type === 'piece-to') {
        // Find which piece can move to that square
        if (parsed.piece) {
            const legalMoves = chess.moves({ verbose: true });
            const candidates = legalMoves.filter(m => m.piece === parsed.piece && m.to === parsed.to);
            if (candidates.length === 1) {
                move = chess.move(candidates[0]);
            } else if (candidates.length > 1) {
                voiceToast('Ambiguous — which ' + (PIECE_SYMBOLS[parsed.piece] || parsed.piece) + '? Say "' + candidates[0].from + ' to ' + parsed.to + '"');
                speak('Which ' + Object.keys(PIECE_NAMES).find(k => PIECE_NAMES[k] === parsed.piece) + '?');
                return false;
            } else {
                voiceToast('No legal ' + (PIECE_SYMBOLS[parsed.piece] || 'piece') + ' move to ' + parsed.to);
                return false;
            }
        } else {
            // Try any piece to that square
            move = chess.move({ to: parsed.to, promotion: 'q' });
        }
    } else if (parsed.type === 'square') {
        // If we have a selected square, complete the move
        if (typeof selectedSquare !== 'undefined' && selectedSquare) {
            move = chess.move({ from: selectedSquare, to: parsed.to, promotion: 'q' });
        } else {
            // Try pawn move to that square
            const legalMoves = chess.moves({ verbose: true });
            const pawnMoves = legalMoves.filter(m => m.piece === 'p' && m.to === parsed.to);
            if (pawnMoves.length === 1) {
                move = chess.move(pawnMoves[0]);
            } else if (pawnMoves.length > 1) {
                voiceToast('Which pawn? Say the column too');
                return false;
            } else {
                voiceToast('No pawn move to ' + parsed.to);
                return false;
            }
        }
    }

    if (!move) {
        voiceToast('Illegal move');
        speak('Illegal move');
        return false;
    }

    // Apply the move visually
    if (gameMode === 'pvp' && typeof submitPvpMove === 'function') {
        // PvP: undo the chess.js move (submitPvpMove will re-apply it)
        chess.undo();
        // Use the PvP submission path
        if (typeof selectedSquare !== 'undefined') selectedSquare = null;
        if (typeof clearHighlights === 'function') clearHighlights();
        submitPvpMove(move.from, move.to, move.flags.includes('p') ? 'q' : null,
            chess.in_checkmate() ? 'checkmate' : chess.in_stalemate() ? 'stalemate' : 'playing');
    } else {
        // AI game: animate + trigger AI response
        if (typeof moveHistory !== 'undefined') moveHistory.push({ move: move.san, color: turn, agent: 'You' });
        if (typeof updateMoveLog === 'function') updateMoveLog();
        if (typeof broadcastMove === 'function') broadcastMove(move.san, 'You', turn);
        if (typeof playSound === 'function') playSound(move.captured ? 'capture' : 'move');
        if (typeof selectedSquare !== 'undefined') selectedSquare = null;

        // Animate the move with arc trajectory
        var moveStr = move.from + move.to;
        if (typeof animateMove === 'function') {
            animateMove(moveStr, function() {
                if (typeof renderPieces === 'function') renderPieces();
                if (typeof highlightSquare === 'function') {
                    if (typeof clearHighlights === 'function') clearHighlights();
                    highlightSquare(move.from, 0x00A8FF);
                    highlightSquare(move.to, 0x22c55e);
                }
                if (typeof checkGameEnd === 'function') checkGameEnd();
                if (typeof updateUndoBtn === 'function') updateUndoBtn();
                if (!chess.game_over() && typeof makeAIMove === 'function') {
                    setTimeout(makeAIMove, 500);
                }
            }, !!move.captured);
        } else {
            // Fallback: instant render if animateMove unavailable
            if (typeof renderPieces === 'function') renderPieces();
            if (typeof highlightSquare === 'function') {
                if (typeof clearHighlights === 'function') clearHighlights();
                highlightSquare(move.from, 0x00A8FF);
                highlightSquare(move.to, 0x22c55e);
            }
            if (typeof checkGameEnd === 'function') checkGameEnd();
            if (!chess.game_over() && typeof makeAIMove === 'function') {
                setTimeout(makeAIMove, 500);
            }
        }
    }

    voiceToast(move.san);
    speak(describeMoveForSpeech(move));
    return true;
}

function describeMoveForSpeech(move) {
    const pieces = { p: 'pawn', n: 'knight', b: 'bishop', r: 'rook', q: 'queen', k: 'king' };
    const name = pieces[move.piece] || 'piece';
    const capture = move.captured ? ' takes ' + (pieces[move.captured] || 'piece') + ' on' : ' to';
    const check = move.san.includes('+') ? ', check' : move.san.includes('#') ? ', checkmate!' : '';
    return name + capture + ' ' + move.to + check;
}

/* ═══════════════════════════════════════════════════════════════
   COMMAND HANDLER (called by Alfred widget or local speech)
   ═══════════════════════════════════════════════════════════════ */
function handleCommand(text) {
    const t = text.toLowerCase().trim();

    // ── Game start ──
    if (/^(?:play|start)\s+(?:as\s+)?white$/i.test(t) || t === 'play white') {
        startGame('play-white');
        voiceToast('Playing as White');
        speak('Starting game as white');
        return true;
    }
    if (/^(?:play|start)\s+(?:as\s+)?black$/i.test(t) || t === 'play black') {
        startGame('play-black');
        voiceToast('Playing as Black');
        speak('Starting game as black');
        return true;
    }
    if (/^(?:watch|spectate)\s*(?:ai|game|match)?$/i.test(t) || t === 'spectate') {
        startGame('spectate');
        voiceToast('Spectating AI vs AI');
        return true;
    }
    if (/^(?:start\s+)?tournament$/i.test(t)) {
        startGame('tournament');
        voiceToast('Tournament started');
        return true;
    }
    if (/^(?:new\s+game|reset|start\s*over|rematch)$/i.test(t)) {
        if (typeof resetGame === 'function') resetGame();
        voiceToast('New game');
        speak('Starting a new game');
        return true;
    }
    if (/^(?:play\s+(?:a\s+)?friend|challenge|invite|pvp)$/i.test(t)) {
        if (typeof showChallengeModal === 'function') showChallengeModal();
        voiceToast('Challenge a friend');
        return true;
    }

    // ── Difficulty ──
    const diffMatch = t.match(/^(?:set\s+)?(?:difficulty|level)\s+(?:to\s+)?(\w+)$/i)
                   || t.match(/^(\w+)\s+(?:difficulty|mode|level)$/i);
    if (diffMatch) {
        const depth = DIFFICULTY_MAP[diffMatch[1]];
        if (depth !== undefined) {
            if (typeof aiDepth !== 'undefined') {
                // Set via the button system
                document.querySelectorAll('.diff-btn[data-depth]').forEach(b => {
                    b.classList.toggle('active', parseInt(b.dataset.depth) === depth);
                });
                aiDepth = depth;
                voiceToast('Difficulty: ' + diffMatch[1]);
                speak('Difficulty set to ' + diffMatch[1]);
                return true;
            }
        }
    }

    // ── Camera ──
    const camMatch = t.match(/^(?:camera|view|angle|switch\s*(?:to)?)\s+(.+)$/i);
    if (camMatch) {
        const mode = CAMERA_NAMES[camMatch[1].trim()];
        if (mode && typeof camModes !== 'undefined' && typeof camModeIdx !== 'undefined') {
            const idx = camModes.indexOf(mode);
            if (idx >= 0) {
                camModeIdx = idx;
                if (typeof toggleCamera === 'function') {
                    // Set to idx-1 so toggleCamera increments to the right one
                    camModeIdx = idx - 1;
                    if (camModeIdx < 0) camModeIdx = camModes.length - 1;
                    toggleCamera();
                }
                return true;
            }
        }
    }
    if (/^(?:rotate|spin|orbit)$/i.test(t)) {
        camModeIdx = -1; toggleCamera(); return true;
    }
    if (/^(?:top\s*(?:down|view)?|overhead|bird'?s?\s*eye)$/i.test(t)) {
        camModeIdx = camModes.indexOf('top-down') - 1; toggleCamera(); return true;
    }

    // ── Theme ──
    const themeMatch = t.match(/^(?:theme|board|set\s*(?:board|theme))\s+(?:to\s+)?(.+)$/i);
    if (themeMatch) {
        const theme = THEME_NAMES[themeMatch[1].trim()];
        if (theme && typeof applyTheme === 'function') {
            applyTheme(theme);
            voiceToast('Theme: ' + theme);
            speak('Board theme set to ' + theme);
            return true;
        }
    }

    // ── Piece style ──
    const styleMatch = t.match(/^(?:piece|pieces|style|set\s*(?:pieces?|style))\s+(?:to\s+)?(.+)$/i);
    if (styleMatch) {
        const style = STYLE_NAMES[styleMatch[1].trim()];
        if (style && typeof setPieceStyle === 'function') {
            setPieceStyle(style);
            voiceToast('Pieces: ' + style);
            return true;
        }
    }

    // ── Flip board ──
    if (/^(?:flip|rotate)\s*(?:the\s*)?board$/i.test(t) || t === 'flip') {
        if (typeof flipBoard === 'function') { flipBoard(); voiceToast('Board flipped'); }
        return true;
    }

    // ── Settings toggles ──
    if (/^(?:toggle\s+)?(?:show\s+)?coord(?:inate)?s?\s*(?:on|off)?$/i.test(t)) {
        if (typeof toggleCoords === 'function') toggleCoords();
        return true;
    }
    if (/^(?:toggle\s+)?highlight(?:s|ing)?\s*(?:on|off)?$/i.test(t)) {
        if (typeof toggleHighlightMoves === 'function') toggleHighlightMoves();
        return true;
    }
    if (/^(?:toggle\s+)?sound(?:s)?\s*(?:on|off)?$/i.test(t) || /^(?:mute|unmute)\s*(?:sound)?$/i.test(t)) {
        if (typeof toggleSound === 'function') toggleSound();
        return true;
    }
    if (/^(?:open\s+)?settings$/i.test(t)) {
        if (typeof toggleSettings === 'function') toggleSettings();
        return true;
    }

    // ── Speed ──
    if (/^(?:speed|set\s+speed)\s+(?:to\s+)?(\d+)x?$/i.test(t)) {
        const speed = parseInt(t.match(/(\d+)/)[1]);
        if ([1, 2, 5].includes(speed) && typeof setSpeed === 'function') {
            setSpeed(speed); voiceToast('Speed: ' + speed + 'x');
            return true;
        }
    }
    if (/^(?:faster|speed\s*up)$/i.test(t)) { if (typeof setSpeed === 'function') setSpeed(5); return true; }
    if (/^(?:slower|slow\s*down)$/i.test(t)) { if (typeof setSpeed === 'function') setSpeed(1); return true; }

    // ── GM actions ──
    if (/^(?:offer\s+)?draw$/i.test(t) || t === 'draw') {
        if (typeof offerDraw === 'function') { offerDraw(); voiceToast('Draw offered'); speak('Draw offered'); }
        return true;
    }
    if (/^(?:accept\s+)?draw\s*(?:offer|accepted)?$/i.test(t) && /accept/i.test(t)) {
        if (typeof acceptDraw === 'function') acceptDraw();
        return true;
    }
    if (/^(?:decline|reject)\s*(?:draw)?$/i.test(t)) {
        if (typeof declineDraw === 'function') declineDraw();
        return true;
    }
    if (/^(?:resign|give\s*up|surrender|i\s*resign|concede)$/i.test(t)) {
        if (typeof confirmResign === 'function') { confirmResign(); speak('Game resigned'); }
        return true;
    }
    if (/^(?:export|show|get)\s*pgn$/i.test(t)) {
        if (typeof exportPGN === 'function') exportPGN();
        return true;
    }
    if (/^(?:copy)\s*pgn$/i.test(t)) {
        if (typeof copyPGN === 'function') copyPGN();
        return true;
    }

    // ── VR ──
    if (/^(?:enter|start|launch)\s*(?:vr|virtual\s*reality|immersive)$/i.test(t)) {
        if (typeof enterVR === 'function') { enterVR(); voiceToast('Entering VR'); }
        return true;
    }

    // ── Lobby ──
    if (/^(?:show|open)\s*(?:the\s+)?lobby$/i.test(t) || t === 'lobby') {
        if (typeof showLobby === 'function') showLobby();
        return true;
    }
    if (/^(?:watch|spectate)\s+(?:live|games?)$/i.test(t)) {
        if (typeof showLobby === 'function') showLobby();
        return true;
    }

    // ── Share ──
    if (/^(?:share|invite)$/i.test(t)) {
        if (typeof shareCurrentGame === 'function') shareCurrentGame();
        return true;
    }

    // ── Game status inquiry ──
    if (/^(?:what'?s?\s+(?:the\s+)?)?(?:status|score|position|whose\s+turn|who'?s?\s+turn)$/i.test(t)) {
        announceStatus();
        return true;
    }

    // ── Hint / suggest ──
    if (/^(?:hint|suggest|what\s+should\s+i\s+(?:play|move)|help\s+me|best\s+move|coach|alfred\s+help)$/i.test(t)) {
        if (typeof showHint === 'function') showHint();
        else suggestMove();
        return true;
    }

    // ── Undo ──
    if (/^(?:undo|take\s*back|go\s*back|revert|undo\s*(?:last\s*)?move)$/i.test(t)) {
        if (typeof undoMove === 'function') {
            undoMove();
            voiceToast('Move undone');
            speak('Move undone');
        } else {
            voiceToast('Undo not available');
        }
        return true;
    }

    // ── Play Alfred's suggestion ──
    if (/^(?:play\s*(?:it|that|this)|do\s*it|make\s*(?:the|that|this)?\s*move|execute|go\s*ahead|yes\s*play|yes)$/i.test(t)) {
        if (window._alfredPendingMove && typeof window.executeAlfredMove === 'function') {
            window.executeAlfredMove(window._alfredPendingMove);
            voiceToast('Playing suggested move');
            speak('Playing the suggested move');
        } else {
            voiceToast('No move pending — say "hint" first');
        }
        return true;
    }

    // ── Difficulty ──
    if (/^(?:difficulty\s+)?(easy|medium|normal|hard|expert|master)$/i.test(t)) {
        const match = t.match(/(easy|medium|normal|hard|expert|master)/i);
        if (match) {
            const depth = DIFFICULTY_MAP[match[1].toLowerCase()];
            if (depth !== undefined && typeof setDifficultyInGame === 'function') {
                setDifficultyInGame(depth);
                voiceToast('Difficulty: ' + match[1]);
                speak('Difficulty set to ' + match[1]);
                return true;
            }
        }
    }

    // ── Legal moves ──
    if (/^(?:(?:show\s+)?legal\s*moves?|what\s+(?:can\s+i|are\s+my)\s+(?:moves?|options?)|my\s+moves?)$/i.test(t)) {
        showLegalMoves();
        return true;
    }

    // ── Voice control commands ──
    if (/^(?:stop\s+)?listen(?:ing)?$/i.test(t) && !(/^listen/i.test(t))) {
        stopListening();
        return true;
    }
    if (/^hands?\s*free\s*(?:mode|on)?$/i.test(t) || /^(?:always|continuous)\s*listen(?:ing)?$/i.test(t)) {
        handsFreeMode = true;
        if (!listening) startListening();
        voiceToast('Hands-free mode ON — all speech goes to Alfred');
        speak('Hands free mode activated. Just speak naturally.');
        updateHandsFreeUI(true);
        return true;
    }
    if (/^(?:hands?\s*free\s*off|stop\s+hands?\s*free|normal\s*mode|push\s*to\s*talk)$/i.test(t)) {
        handsFreeMode = false;
        voiceToast('Hands-free mode OFF');
        speak('Hands free mode off');
        updateHandsFreeUI(false);
        return true;
    }
    if (/^(?:chess\s+)?(?:voice\s+)?(?:help|commands?)$/i.test(t)) {
        showVoiceHelp();
        return true;
    }

    // ── Try to parse as a move ──
    const parsed = parseMoveCommand(t);
    if (parsed) {
        return executeVoiceMove(parsed);
    }

    return false;
}

/* ═══════════════════════════════════════════════════════════════
   GAME INQUIRY HELPERS
   ═══════════════════════════════════════════════════════════════ */
function announceStatus() {
    if (typeof chess === 'undefined' || !chess) {
        voiceToast('No game in progress');
        return;
    }
    const turn = chess.turn() === 'w' ? 'White' : 'Black';
    let status = turn + "'s turn";
    if (chess.in_check()) status += ' (in check!)';
    if (chess.in_checkmate()) status = 'Checkmate! ' + (chess.turn() === 'w' ? 'Black' : 'White') + ' wins';
    if (chess.in_stalemate()) status = 'Stalemate — draw';
    if (chess.in_draw()) status = 'Draw';

    const moves = typeof moveHistory !== 'undefined' ? moveHistory.length : 0;
    voiceToast(status + ' (' + moves + ' moves)');
    speak(status);
}

function suggestMove() {
    if (typeof chess === 'undefined' || !chess) return;
    if (typeof stockfishWhite === 'undefined' && typeof stockfishBlack === 'undefined') {
        voiceToast('No AI available for hints');
        return;
    }
    const worker = chess.turn() === 'w' ? stockfishWhite : stockfishBlack;
    if (!worker) { voiceToast('AI not available'); return; }

    voiceToast('Thinking...');
    if (typeof getAIMove === 'function') {
        getAIMove(worker, chess.fen(), 10).then(bestMove => {
            if (bestMove) {
                voiceToast('Try: ' + bestMove.substring(0, 2) + ' to ' + bestMove.substring(2, 4));
                speak('Try ' + bestMove.substring(0, 2) + ' to ' + bestMove.substring(2, 4));
            }
        });
    }
}

function showLegalMoves() {
    if (typeof chess === 'undefined' || !chess) return;
    const moves = chess.moves();
    if (moves.length === 0) { voiceToast('No legal moves'); return; }
    const sample = moves.slice(0, 10).join(', ');
    voiceToast(moves.length + ' moves: ' + sample + (moves.length > 10 ? '…' : ''));
    speak('You have ' + moves.length + ' legal moves');
}

function showVoiceHelp() {
    const help = [
        '🎙️ Chess Voice Commands:',
        '• "Hey Alfred" — wake word (speak then command)',
        '• "hands free" — always-on voice mode',
        '• "play white/black" — start game',
        '• "pawn to e4" or "knight f3" — move',
        '• "e2 to e4" — move by squares',
        '• "castle kingside/queenside"',
        '• "hint" / "suggest" — get AI suggestion',
        '• "play it" / "do it" — execute suggestion',
        '• "undo" — take back last move',
        '• "new game" / "resign" / "offer draw"',
        '• "camera orbit/cinematic/top-down"',
        '• "theme obsidian/marble/crystal"',
        '• "pieces gothic/glass/staunton"',
        '• "easy / medium / hard / expert"',
        '• "flip board" / "coordinates"',
        '• "legal moves" / "status"',
        '• "speed 1x/2x/5x" / "faster/slower"',
        '• "enter VR" / "lobby" / "share"',
        '• "stop listening" / "hands free off"',
    ];
    voiceToast(help.join('\n'));
    speak('Say a piece name then a square, like pawn to e4. Say hint for suggestions, or help for more commands.');
}

/* ═══════════════════════════════════════════════════════════════
   SPEECH RECOGNITION
   ═══════════════════════════════════════════════════════════════ */
function createRecognition() {
    const rec = new SpeechRec();
    rec.lang = 'en-US';
    rec.continuous = true;
    rec.interimResults = true;
    rec.maxAlternatives = 3;

    let finalTranscript = '';
    let interimTranscript = '';

    rec.onstart = () => {
        listening = true;
        updateMicUI(true);
    };

    rec.onend = () => {
        if (continuous && listening) {
            // Auto-restart for continuous mode
            try { rec.start(); } catch(e) {}
            return;
        }
        listening = false;
        updateMicUI(false);
    };

    rec.onerror = (e) => {
        if (e.error === 'not-allowed') {
            voiceToast('Microphone access denied');
        } else if (e.error !== 'no-speech' && e.error !== 'aborted') {
            console.warn('[ChessVoice] Recognition error:', e.error);
        }
        if (e.error === 'not-allowed' || e.error === 'service-not-allowed') {
            listening = false;
            continuous = false;
            updateMicUI(false);
        }
    };

    rec.onresult = (event) => {
        interimTranscript = '';
        for (let i = event.resultIndex; i < event.results.length; i++) {
            const result = event.results[i];
            if (result.isFinal) {
                finalTranscript = result[0].transcript.trim();
                if (finalTranscript) {
                    processVoiceInput(finalTranscript);
                    finalTranscript = '';
                }
            } else {
                interimTranscript += result[0].transcript;
                updateInterimDisplay(interimTranscript);
            }
        }
    };

    return rec;
}

function processVoiceInput(text) {
    const t = text.toLowerCase().trim();
    updateInterimDisplay('');

    // Show what was heard
    if (voiceIndicator) {
        voiceIndicator.querySelector('.cv-heard').textContent = '"' + text + '"';
        voiceIndicator.querySelector('.cv-heard').style.opacity = '1';
        setTimeout(() => {
            if (voiceIndicator) voiceIndicator.querySelector('.cv-heard').style.opacity = '0.6';
        }, 2000);
    }

    // Wake word detection: "Hey Alfred" / "Alfred" / "OK Alfred"
    const wakeMatch = t.match(/^(?:hey|ok|hi|yo)?\s*alfred[,.]?\s*(.*)/i);
    if (wakeMatch) {
        const afterWake = wakeMatch[1] ? wakeMatch[1].trim() : '';
        if (afterWake) {
            // Wake word + command in one phrase: "Hey Alfred, what's the best move?"
            if (!handleCommand(afterWake)) {
                // Not a chess command — send to Alfred as chat
                sendToAlfred(afterWake);
            }
            return;
        }
        // Just wake word alone — activate for next utterance
        wakeWordActive = true;
        speak('Yes?');
        voiceToast('Alfred is listening...');
        setTimeout(() => { wakeWordActive = false; }, 8000); // 8s window
        return;
    }

    // If wake word was recently spoken, treat anything as a command/chat
    if (wakeWordActive) {
        wakeWordActive = false;
        if (!handleCommand(t)) {
            sendToAlfred(t);
        }
        return;
    }

    // Hands-free mode — all speech goes to command handler or Alfred
    if (handsFreeMode) {
        if (!handleCommand(t)) {
            sendToAlfred(t);
        }
        return;
    }

    // Standard mode — try chess commands first
    if (handleCommand(t)) return;

    // Pass to Alfred widget if present
    sendToAlfred(t);
}

function sendToAlfred(text) {
    if (window.AlfredAPI && typeof window.AlfredAPI.addUserMsg === 'function') {
        // Open the Alfred widget panel if not visible
        const panel = document.querySelector('.alfred-chat-panel, #alfredPanel');
        if (panel && !panel.classList.contains('show') && !panel.classList.contains('open')) {
            if (window.AlfredAPI.toggle) window.AlfredAPI.toggle();
        }
        window.AlfredAPI.addUserMsg('🎙️ ' + text);
    }
}

function updateInterimDisplay(text) {
    if (!voiceIndicator) return;
    const el = voiceIndicator.querySelector('.cv-interim');
    if (el) el.textContent = text || '';
}

function updateHandsFreeUI(active) {
    const hfBtn = document.querySelector('.cv-handsfree-btn');
    if (hfBtn) {
        hfBtn.classList.toggle('active', active);
        hfBtn.textContent = active ? '🎙️ Hands-Free ON' : '🙊 Hands-Free';
    }
    if (voiceIndicator) {
        const label = voiceIndicator.querySelector('.cv-label');
        if (label) label.textContent = active ? '🎙️ Hands-Free Active' : '🎙️ Listening...';
    }
}

/* ═══════════════════════════════════════════════════════════════
   MIC CONTROL
   ═══════════════════════════════════════════════════════════════ */
function startListening() {
    if (!recognition) recognition = createRecognition();
    if (listening) return;
    continuous = true;
    try {
        recognition.start();
        voiceToast('Voice control active — speak a command');
    } catch(e) {
        // Already started
    }
}

function stopListening() {
    continuous = false;
    if (recognition) {
        try { recognition.stop(); } catch(e) {}
    }
    listening = false;
    updateMicUI(false);
    voiceToast('Voice control stopped');
}

function toggleListening() {
    if (listening) stopListening();
    else startListening();
}

function updateMicUI(active) {
    if (micBtn) {
        micBtn.classList.toggle('active', active);
        micBtn.title = active ? 'Voice ON — click to stop' : 'Voice Control — click to start';
    }
    if (voiceIndicator) {
        voiceIndicator.classList.toggle('active', active);
    }
}

/* ═══════════════════════════════════════════════════════════════
   MOVE ANNOUNCER (auto-announce opponent moves)
   ═══════════════════════════════════════════════════════════════ */
let lastAnnouncedMoveCount = 0;

function checkAndAnnounceMoves() {
    if (typeof chess === 'undefined' || !chess) return;
    if (!listening) return; // Only announce when voice is active
    if (typeof moveHistory === 'undefined') return;

    const count = moveHistory.length;
    if (count > lastAnnouncedMoveCount) {
        const lastMove = moveHistory[count - 1];
        if (lastMove && lastMove.agent !== 'You') {
            speak(lastMove.agent + ' plays ' + lastMove.move);
        }
        lastAnnouncedMoveCount = count;
    }
}

// Poll for move announcements every 2 seconds
setInterval(checkAndAnnounceMoves, 2000);

/* ═══════════════════════════════════════════════════════════════
   UI INJECTION
   ═══════════════════════════════════════════════════════════════ */
function injectUI() {
    // ── Mic button in HUD ──
    const hudRight = document.querySelector('.hud-right');
    if (hudRight) {
        micBtn = document.createElement('button');
        micBtn.className = 'hud-btn cv-mic-btn';
        micBtn.innerHTML = '🎙️';
        micBtn.title = 'Voice Control — click to start';
        micBtn.onclick = toggleListening;
        hudRight.insertBefore(micBtn, hudRight.firstChild);

        // Hands-free button
        const hfBtn = document.createElement('button');
        hfBtn.className = 'hud-btn cv-handsfree-btn';
        hfBtn.innerHTML = '🙊 Hands-Free';
        hfBtn.title = 'Toggle hands-free mode — speak "Hey Alfred" or continuous listening';
        hfBtn.style.cssText = 'font-size:0.6rem;padding:4px 8px;white-space:nowrap;';
        hfBtn.onclick = () => {
            handsFreeMode = !handsFreeMode;
            if (handsFreeMode) {
                if (!listening) startListening();
                voiceToast('Hands-free ON — say "Hey Alfred" or just speak');
                speak('Hands free mode on');
            } else {
                voiceToast('Hands-free OFF');
                speak('Hands free off');
            }
            updateHandsFreeUI(handsFreeMode);
        };
        hudRight.insertBefore(hfBtn, micBtn.nextSibling);
    }

    // ── Floating voice indicator ──
    voiceIndicator = document.createElement('div');
    voiceIndicator.className = 'cv-indicator';
    voiceIndicator.innerHTML = `
        <div class="cv-dot"></div>
        <div class="cv-label">🎙️ Listening...</div>
        <div class="cv-heard"></div>
        <div class="cv-interim"></div>
    `;
    document.body.appendChild(voiceIndicator);

    // ── Mic button on mode selection screen ──
    const modePanel = document.querySelector('.mode-panel');
    if (modePanel) {
        const voiceRow = document.createElement('div');
        voiceRow.className = 'diff-row';
        voiceRow.style.marginTop = '.5rem';
        voiceRow.innerHTML = '<button class="diff-btn cv-start-voice" style="flex:1;background:linear-gradient(135deg,#7d00ff,#00d4ff)" onclick="window.chessVoice.toggle()">🎙️ Voice Control</button>';
        modePanel.appendChild(voiceRow);
    }

    // ── CSS ──
    const style = document.createElement('style');
    style.textContent = `
        .cv-mic-btn { position: relative; }
        .cv-mic-btn.active { animation: cv-pulse 1.5s ease-in-out infinite; background: rgba(125,0,255,0.4) !important; }
        .cv-handsfree-btn { transition: all 0.3s; }
        .cv-handsfree-btn.active { background: rgba(34,197,94,0.4) !important; border-color: rgba(34,197,94,0.6) !important; animation: cv-pulse 2s ease-in-out infinite; }
        @keyframes cv-pulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(125,0,255,0.5); }
            50% { box-shadow: 0 0 0 8px rgba(125,0,255,0); }
        }

        .cv-indicator {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(10,10,30,0.9);
            border: 1px solid rgba(125,0,255,0.4);
            border-radius: 24px;
            padding: 8px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 950;
            font-family: system-ui, sans-serif;
            font-size: 13px;
            color: #ccc;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            backdrop-filter: blur(8px);
        }
        .cv-indicator.active { opacity: 1; }

        .cv-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: #666;
            transition: background 0.3s;
        }
        .cv-indicator.active .cv-dot {
            background: #ff3040;
            animation: cv-dot-pulse 1s ease-in-out infinite;
        }
        @keyframes cv-dot-pulse {
            0%,100% { opacity: 1; box-shadow: 0 0 0 0 rgba(255,48,64,0.5); }
            50% { opacity: 0.6; box-shadow: 0 0 0 4px rgba(255,48,64,0); }
        }

        .cv-heard {
            color: #00d4ff;
            font-style: italic;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            opacity: 0.6;
            transition: opacity 0.3s;
        }
        .cv-interim {
            color: rgba(255,255,255,0.4);
            font-size: 11px;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .cv-label { white-space: nowrap; }

        .cv-start-voice:hover { opacity: 0.9; transform: scale(1.02); }

        @media(max-width:768px) {
            .cv-indicator { top: auto; bottom: 80px; font-size: 11px; padding: 6px 14px; }
        }
    `;
    document.head.appendChild(style);
}

/* ═══════════════════════════════════════════════════════════════
   INIT & PUBLIC API
   ═══════════════════════════════════════════════════════════════ */
function init() {
    injectUI();
    console.log('[ChessVoice] Voice commander ready — click 🎙️ or say "help"');
}

// Expose public API
window.chessVoice = {
    handleCommand,
    start: startListening,
    stop: stopListening,
    toggle: toggleListening,
    speak,
    isListening: () => listening,
    isHandsFree: () => handsFreeMode,
    isSpeechEnabled: () => speechEnabled,
    setHandsFree(on) {
        handsFreeMode = !!on;
        if (handsFreeMode && !listening) startListening();
        updateHandsFreeUI(handsFreeMode);
    },
    setVolume(v) { alfredVolume = Math.max(0, Math.min(1, v)); },
    setSpeechEnabled(on) {
        speechEnabled = !!on;
        persistSpeechEnabled();
        if (!speechEnabled && window.speechSynthesis) {
            window.speechSynthesis.cancel();
        }
        return speechEnabled;
    },
    setVoiceByName(name) {
        const voices = window.speechSynthesis.getVoices();
        const v = voices.find(v => v.name === name);
        if (v) alfredVoice = v;
    },
    resetVoice() { alfredVoice = null; findAlfredVoice(); },
};

// Init when DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

})();
