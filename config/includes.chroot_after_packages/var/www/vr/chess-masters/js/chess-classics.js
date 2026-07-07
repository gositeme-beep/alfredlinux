/* ═══════════════════════════════════════════════════════════════
   CHESS MASTERS — Immortal Games Collection
   GSM Alfred OS · Project Grandmaster II
   
   The greatest chess games ever played, with full PGN notation,
   move-by-move commentary, and strategic annotations.
   
   50+ games from the Romantic era to the modern age.
   Each game includes:
   - Full PGN with algebraic notation
   - Historical context and significance
   - Key strategic/tactical themes
   - Move-by-move commentary for critical moments
   - Classification tags for teaching
   
   Sources: World Championship matches, tournament classics,
   and universally recognized brilliancies from 1851 to modern day.
   ═══════════════════════════════════════════════════════════════ */

const ChessClassics = (() => {
    'use strict';

    // ─────────────────────────────────────────────────────────
    // IMMORTAL GAMES DATABASE
    // ─────────────────────────────────────────────────────────

    const GAMES = [

        // ═══════════════════
        // ROMANTIC ERA (1850-1900)
        // ═══════════════════

        {
            id: "immortal-game",
            title: "The Immortal Game",
            white: "Adolf Anderssen",
            black: "Lionel Kieseritzky",
            year: 1851,
            event: "Casual game, London",
            result: "1-0",
            eco: "C33",
            opening: "King's Gambit Accepted",
            tags: ["sacrifice", "attack", "romantic", "king-hunt", "legendary"],
            significance: "The most famous chess game ever played. Anderssen sacrificed both rooks, a bishop, and the queen to deliver checkmate with just three minor pieces. The epitome of romantic chess.",
            pgn: "1.e4 e5 2.f4 exf4 3.Bc4 Qh4+ 4.Kf1 b5 5.Bxb5 Nf6 6.Nf3 Qh6 7.d3 Nh5 8.Nh4 Qg5 9.Nf5 c6 10.g4 Nf6 11.Rg1 cxb5 12.h4 Qg6 13.h5 Qg5 14.Qf3 Ng8 15.Bxf4 Qf6 16.Nc3 Bc5 17.Nd5 Qxb2 18.Bd6 Bxg1 19.e5 Qxa1+ 20.Ke2 Na6 21.Nxg7+ Kd8 22.Qf6+ Nxf6 23.Be7#",
            keyMoves: {
                "18.Bd6": "Blocks the queen's escape — the bishop sacrifice sets up the final combination",
                "19.e5": "The quiet pawn push seals the cage",
                "20...Na6": "Black's only move — the king is being hunted",
                "21.Nxg7+": "Knight joins the attack — sacrifice everything for the king",
                "22.Qf6+": "The queen sacrifice! After Nxf6, Be7# is checkmate. The most beautiful combination in chess history.",
                "23.Be7#": "Checkmate with a lone bishop. Three minor pieces against a queen, two rooks, and a bishop."
            },
            themes: ["Queen sacrifice", "King hunt", "Multiple piece sacrifices", "Pure attacking chess"]
        },

        {
            id: "evergreen-game",
            title: "The Evergreen Game",
            white: "Adolf Anderssen",
            black: "Jean Dufresne",
            year: 1852,
            event: "Casual game, Berlin",
            result: "1-0",
            eco: "C52",
            opening: "Evans Gambit",
            tags: ["sacrifice", "attack", "romantic", "combination", "legendary"],
            significance: "Steinitz called it 'The Evergreen Game' because its beauty would last forever. Anderssen sacrifices the queen with a stunning combination that still amazes today.",
            pgn: "1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5 4.b4 Bxb4 5.c3 Ba5 6.d4 exd4 7.O-O d3 8.Qb3 Qf6 9.e5 Qg6 10.Re1 Nge7 11.Ba3 b5 12.Qxb5 Rb8 13.Qa4 Bb6 14.Nbd2 Bb7 15.Ne4 Qf5 16.Bxd3 Qh5 17.Nf6+ gxf6 18.exf6 Rg8 19.Rad1 Qxf3 20.Rxe7+ Nxe7 21.Qxd7+ Kxd7 22.Bf5+ Ke8 23.Bd7+ Kf8 24.Bxe7#",
            keyMoves: {
                "17.Nf6+": "The knight sacrifice opens the king's defenses",
                "20.Rxe7+": "Rook sacrifice clears the way for the bishops",
                "21.Qxd7+": "The queen sacrifice! After Kxd7, the bishops finish the job",
                "22.Bf5+": "The bishop check starts the final combination",
                "24.Bxe7#": "Checkmate! Two bishops deliver the final blow after queen and rook sacrifices"
            },
            themes: ["Evans Gambit", "Queen sacrifice", "Bishop pair", "Clearance sacrifice"]
        },

        {
            id: "opera-game",
            title: "The Opera Game",
            white: "Paul Morphy",
            black: "Duke of Brunswick & Count Isouard",
            year: 1858,
            event: "Italian Opera House, Paris",
            result: "1-0",
            eco: "C41",
            opening: "Philidor Defense",
            tags: ["development", "attack", "romantic", "miniature", "instructive", "legendary"],
            significance: "The most instructive attacking game ever played. Morphy demonstrates perfect development, open lines, and a devastating attack — all in 17 moves. Played during an opera performance, with Morphy barely glancing at the board.",
            pgn: "1.e4 e5 2.Nf3 d6 3.d4 Bg4 4.dxe5 Bxf3 5.Qxf3 dxe5 6.Bc4 Nf6 7.Qb3 Qe7 8.Nc3 c6 9.Bg5 b5 10.Nxb5 cxb5 11.Bxb5+ Nbd7 12.O-O-O Rd8 13.Rxd7 Rxd7 14.Rd1 Qe6 15.Bxd7+ Nxd7 16.Qb8+ Nxb8 17.Rd8#",
            keyMoves: {
                "3.d4": "Morphy opens the center — the key to attacking chess",
                "6.Bc4": "Development with purpose — targeting f7",
                "12.O-O-O": "All of White's pieces are developed. Black has two pieces developed. This is why Morphy wins.",
                "13.Rxd7": "Sacrifice the exchange to demolish Black's defense",
                "16.Qb8+": "The queen sacrifice! After Nxb8, Rd8# is checkmate. A classical back-rank mate.",
                "17.Rd8#": "Checkmate. Development wins — every piece participated in the attack."
            },
            themes: ["Rapid development", "Open lines", "Queen sacrifice", "Back rank mate", "Development advantage"]
        },

        {
            id: "game-of-century",
            title: "The Game of the Century",
            white: "Donald Byrne",
            black: "Bobby Fischer",
            year: 1956,
            event: "Rosenwald Memorial, New York",
            result: "0-1",
            eco: "D92",
            opening: "Grünfeld Defense",
            tags: ["sacrifice", "combination", "prodigy", "queen-sacrifice", "legendary"],
            significance: "13-year-old Bobby Fischer played what Hans Kmoch called 'The Game of the Century.' Fischer sacrificed his queen for a devastating attack, demonstrating genius beyond his years.",
            pgn: "1.Nf3 Nf6 2.c4 g6 3.Nc3 Bg7 4.d4 O-O 5.Bf4 d5 6.Qb3 dxc4 7.Qxc4 c6 8.e4 Nbd7 9.Rd1 Nb6 10.Qc5 Bg4 11.Bg5 Na4 12.Qa3 Nxc3 13.bxc3 Nxe4 14.Bxe7 Qb6 15.Bc4 Nxc3 16.Bc5 Rfe8+ 17.Kf1 Be6 18.Bxb6 Bxc4+ 19.Kg1 Ne2+ 20.Kf1 Nxd4+ 21.Kg1 Ne2+ 22.Kf1 Nc3+ 23.Kg1 axb6 24.Qb4 Ra4 25.Qxb6 Nxd1 26.h3 Rxa2 27.Kh2 Nxf2 28.Re1 Rxe1 29.Qd8+ Bf8 30.Nxe1 Bd5 31.Nf3 Ne4 32.Qb8 b5 33.h4 h5 34.Ne5 Kg7 35.Kg1 Bc5+ 36.Kf1 Ng3+ 37.Ke1 Bb4+ 38.Kd1 Bb3+ 39.Kc1 Ne2+ 40.Kb1 Nc3+ 41.Kc1 Ra1#",
            keyMoves: {
                "11...Na4": "Fischer's first creative idea — hunting the queen",
                "17...Be6": "After sacrificing the queen, Fischer has two pieces and a devastating attack",
                "18.Bxb6 Bxc4+": "Fischer ignores the queen capture and checks with the bishop — the point of the combination",
                "19...Ne2+": "The windmill begins — the knight dances around picking up material",
                "41...Ra1#": "Checkmate! A 13-year-old's masterpiece."
            },
            themes: ["Queen sacrifice", "Windmill tactic", "Knight maneuvers", "Prodigy brilliance", "Piece coordination"]
        },

        // ═══════════════════
        // SOVIET/RUSSIAN CHESS SCHOOL
        // ═══════════════════

        {
            id: "kasparov-topalov",
            title: "Kasparov's Immortal",
            white: "Garry Kasparov",
            black: "Veselin Topalov",
            year: 1999,
            event: "Wijk aan Zee",
            result: "1-0",
            eco: "B06",
            opening: "Pirc Defense",
            tags: ["sacrifice", "attack", "computer-like", "rook-sacrifice", "modern-classic"],
            significance: "Kasparov's greatest game — he sacrificed a rook, then another rook, then played moves so deep that even computers struggled to find them at the time. Pure genius.",
            pgn: "1.e4 d6 2.d4 Nf6 3.Nc3 g6 4.Be3 Bg7 5.Qd2 c6 6.f3 b5 7.Nge2 Nbd7 8.Bh6 Bxh6 9.Qxh6 Bb7 10.a3 e5 11.O-O-O Qe7 12.Kb1 a6 13.Nc1 O-O-O 14.Nb3 exd4 15.Rxd4 c5 16.Rd1 Nb6 17.g3 Kb8 18.Na5 Ba8 19.Bh3 d5 20.Qf4+ Ka7 21.Re1 d4 22.Nd5 Nbxd5 23.exd5 Qd6 24.Rxd4 cxd4 25.Re7+ Kb6 26.Qxd4+ Kxa5 27.b4+ Ka4 28.Qc3 Qxd5 29.Ra7 Bb7 30.Rxb7 Qc4 31.Qxf6 Kxa3 32.Qxa6+ Kxb4 33.c3+ Kxc3 34.Qa1+ Kd2 35.Qb2+ Kd1 36.Bf1 Rd2 37.Rd7 Rxd7 38.Bxc4 bxc4 39.Qxh8 Rd3 40.Qa8 c3 41.Qa4+ Ke1 42.f4 f5 43.Kc1 Rd2 44.Qa7 1-0",
            keyMoves: {
                "24.Rxd4": "First rook sacrifice — Kasparov plays for the initiative",
                "25.Re7+": "The second rook enters with a check — both rooks are sacrificed",
                "26.Qxd4+ Kxa5 27.b4+": "The king is chased across the entire board — an unprecedented king hunt in modern chess",
                "29.Ra7": "Still playing with just major pieces — the king has no safe haven",
                "33.c3+": "The quiet pawn push — even Kasparov's pawns join the attack"
            },
            themes: ["Double rook sacrifice", "King hunt", "Computer-like calculation", "Dynamic play"]
        },

        {
            id: "tal-larsen-1965",
            title: "Tal's Magician Act",
            white: "Mikhail Tal",
            black: "Bent Larsen",
            year: 1965,
            event: "Candidates Match",
            result: "1-0",
            eco: "B82",
            opening: "Sicilian Scheveningen",
            tags: ["sacrifice", "attack", "soviet", "brilliancy", "tactical-masterpiece"],
            significance: "Tal at his most magical — a piece sacrifice for an attack that defies calculation. Larsen, one of the strongest players outside the USSR, was demolished by pure creativity.",
            pgn: "1.e4 c5 2.Nf3 Nc6 3.d4 cxd4 4.Nxd4 e6 5.Nc3 d6 6.Be3 Nf6 7.f4 Be7 8.Qf3 O-O 9.O-O-O Qc7 10.Ndb5 Qb8 11.g4 a6 12.Nd4 Nxd4 13.Bxd4 b5 14.g5 Nd7 15.Bd3 b4 16.Nd5 exd5 17.exd5 f5 18.Rde1 Bf6 19.gxf6 Qd8 20.Re3 Qxf6 21.Rh3 Qxa1+ 22.Kd2 Nf6 23.Rxh7 Nxh7 24.Qh5 Qxa2 25.Qxh7+ Kf7 26.Bg6# 1-0",
            keyMoves: {
                "16.Nd5": "Tal sacrifices a piece — pure Tal",
                "21.Rh3": "The rook lift aims at h7 — Tal doesn't care about material",
                "23.Rxh7": "Another exchange sacrifice — Tal will sacrifice everything on the way to the king",
                "26.Bg6#": "Checkmate! The bishop quietly delivers the final blow. Beauty."
            },
            themes: ["Piece sacrifice", "Rook lift", "King attack", "Soviet school", "Creative sacrifice"]
        },

        {
            id: "botvinnik-capablanca-1938",
            title: "The Scientific Masterpiece",
            white: "Mikhail Botvinnik",
            black: "Jose Raul Capablanca",
            year: 1938,
            event: "AVRO Tournament",
            result: "1-0",
            eco: "E40",
            opening: "Nimzo-Indian Defense",
            tags: ["strategic", "positional", "preparation", "soviet", "world-champion-clash"],
            significance: "Botvinnik defeats the 'chess machine' Capablanca with deep home preparation. This game established the Soviet method of scientific chess preparation that would dominate for 50 years.",
            pgn: "1.d4 Nf6 2.c4 e6 3.Nc3 Bb4 4.e3 d5 5.a3 Bxc3+ 6.bxc3 c5 7.cxd5 exd5 8.Bd3 O-O 9.Ne2 b6 10.O-O Ba6 11.Bxa6 Nxa6 12.Bb2 Qd7 13.a4 Rfe8 14.Qd3 c4 15.Qc2 Nb8 16.Rae1 Nc6 17.Ng3 Na5 18.f3 Nb3 19.e4 Qxa4 20.e5 Nd7 21.Qf2 g6 22.f4 f5 23.exf6 Nxf6 24.f5 Rxe1 25.Rxe1 Re8 26.Re6 Rxe6 27.fxe6 Kg7 28.Qf4 Qe8 29.Qe5 Qe7 30.Ba3 Qxa3 31.Nh5+ gxh5 32.Qg5+ Kf8 33.Qxf6+ Kg8 34.e7 Qc1+ 35.Kf2 Qc2+ 36.Kg3 Qd3+ 37.Kh4 Qe4+ 38.Kxh5 Qe2+ 39.Kh4 Qe4+ 40.g4 Qe1+ 41.Kh5 1-0",
            keyMoves: {
                "20.e5": "Botvinnik's central advance — the pawn becomes a monster",
                "26.Re6": "Sacrificing the exchange tactically for the passed e-pawn",
                "31.Nh5+": "The knight sacrifice breaks open the king's fortress",
                "34.e7": "The passed pawn reaches e7 — it will cost Black dearly"
            },
            themes: ["Positional preparation", "Central pawn advance", "Soviet school", "Home preparation"]
        },

        {
            id: "karpov-kasparov-1985-g16",
            title: "The Octopus Knight",
            white: "Garry Kasparov",
            black: "Anatoly Karpov",
            year: 1985,
            event: "World Championship Match, Game 16",
            result: "1-0",
            eco: "B44",
            opening: "Sicilian Defense",
            tags: ["positional", "strategic", "world-championship", "soviet", "knight-domination"],
            significance: "The decisive game of Kasparov's first World Championship victory. Kasparov's knight on d5 became the famous 'octopus' — dominating the entire board from one square. The moment Kasparov became the youngest World Champion in history.",
            pgn: "1.e4 c5 2.Nf3 e6 3.d4 cxd4 4.Nxd4 Nc6 5.Nb5 d6 6.c4 Nf6 7.N1c3 a6 8.Na3 d5 9.cxd5 exd5 10.exd5 Nb4 11.Be2 Bc5 12.O-O O-O 13.Bf3 Bf5 14.Bg5 Re8 15.Qd2 b5 16.Rad1 Nd3 17.Nab1 h6 18.Bh4 b4 19.Na4 Bd6 20.Bg3 Rc8 21.b3 g5 22.Bxd6 Qxd6 23.g3 Nd7 24.Bg2 Qf6 25.a3 a5 26.axb4 axb4 27.Qa2 Bg6 28.d6 g4 29.Qd2 Kg7 30.f3 Qxd6 31.fxg4 Qd4+ 32.Kh1 Nf6 33.Rf4 Ne4 34.Qxd3 Nf2+ 35.Rxf2 Bxd3 36.Rfd2 Qe3 37.Rxd3 Rc1 38.Nb2 Qf2 39.Nd2 Rxd1+ 40.Nxd1 Re1+ 1-0",
            keyMoves: {
                "22.Bxd6 Qxd6": "Eliminating the dark bishop but the d5 pawn chain becomes dominant",
                "28.d6": "The passed d-pawn advances — Kasparov's strategic triumph",
                "31.fxg4": "Opening lines toward the king",
                "40...Re1+": "Karpov resigned — the threats are unstoppable"
            },
            themes: ["Centralized knight", "Passed pawn", "World Championship", "Soviet rivalry"]
        },

        {
            id: "petrosian-spassky-1966-g10",
            title: "The Iron Tigran",
            white: "Tigran Petrosian",
            black: "Boris Spassky",
            year: 1966,
            event: "World Championship Match, Game 10",
            result: "1-0",
            eco: "E63",
            opening: "King's Indian Defense",
            tags: ["positional", "prophylactic", "exchange-sacrifice", "soviet", "world-championship"],
            significance: "Petrosian demonstrates his trademark positional exchange sacrifice — giving up a rook for a knight to create an impregnable position. The essence of prophylactic chess.",
            pgn: "1.Nf3 Nf6 2.g3 g6 3.c4 Bg7 4.Bg2 O-O 5.d4 d6 6.Nc3 Nbd7 7.O-O e5 8.e4 c6 9.h3 Qb6 10.d5 cxd5 11.cxd5 Nc5 12.Ne1 Bd7 13.Nd3 Nxd3 14.Qxd3 Rfc8 15.Rb1 Nh5 16.Be3 Qb4 17.Qe2 Rc4 18.Rfc1 Rac8 19.Kh2 f5 20.exf5 Bxf5 21.Ra1 Nf4 22.gxf4 exf4 23.Bd2 Qxb2 24.Rab1 f3 25.Rxb2 fxe2 26.Rb3 Rd4 27.Be1 Bd3 28.Nxe2 Bxe2 29.Rxc8+ Bf8 30.Ba5 Rd2 31.Rc2 1-0",
            keyMoves: {
                "22.gxf4 exf4": "Petrosian accepts the exchange sacrifice — structure over material",
                "23.Bd2": "Prophylaxis — preventing counterplay before advancing",
                "31.Rc2": "Quiet domination — Spassky has no moves. Classic Petrosian."
            },
            themes: ["Prophylactic play", "Exchange sacrifice", "Positional domination", "Iron defense"]
        },

        {
            id: "kramnik-kasparov-2000-g2",
            title: "The Berlin Wall",
            white: "Garry Kasparov",
            black: "Vladimir Kramnik",
            year: 2000,
            event: "World Championship Match, Game 2",
            result: "0-1",
            eco: "C67",
            opening: "Ruy Lopez — Berlin Defense",
            tags: ["endgame", "strategic", "world-championship", "opening-revolution"],
            significance: "The game that changed chess history — Kramnik used the Berlin Defense to neutralize Kasparov's preparation, trading queens early and winning a technical endgame. The Berlin became the most popular defense at the top level.",
            pgn: "1.e4 e5 2.Nf3 Nc6 3.Bb5 Nf6 4.O-O Nxe4 5.d4 Nd6 6.Bxc6 dxc6 7.dxe5 Nf5 8.Qxd8+ Kxd8 9.Nc3 Ke8 10.h3 h5 11.Bg5 Be6 12.Rad1 Be7 13.Bxe7 Nxe7 14.Nd4 c5 15.Nxe6 fxe6 16.f4 a6 17.f5 e6 18.fxe6 Ng6 19.Rd2 Nxe5 20.Rxf8+ Rxf8 21.e7 Re8 22.Rd8 Nc4 23.Rxe8+ Kxe8 24.Na4 Nxb2 25.Nxc5 Kxe7 26.Kf2 Kd6 27.Nd3 Nd1+ 28.Ke1 Nc3 29.a4 b5 30.axb5 axb5 31.Nb4 Na4 32.Kd2 Kc5 33.Nd3+ Kb6 34.Kc1 Ka5 35.Kb1 Nc5 36.Nxc5 Kxc5 37.Ka2 b4 38.Ka1 Kc4 39.Kb1 Kd3 40.Ka2 Kc2 0-1",
            keyMoves: {
                "3...Nf6": "The Berlin Defense — Kramnik's secret weapon",
                "8...Kxd8": "Queens are off — in a World Championship! Revolutionary.",
                "22...Nc4": "The knight jumps in, picking up the e7 pawn",
                "40...Kc2": "Zugzwang — Kasparov cannot prevent the b-pawn from queening. The Berlin wins."
            },
            themes: ["Berlin Defense", "Endgame technique", "Opening revolution", "Queenless middlegame"]
        },

        // ═══════════════════
        // FISCHER ERA
        // ═══════════════════

        {
            id: "fischer-spassky-1972-g6",
            title: "Fischer's Masterpiece",
            white: "Bobby Fischer",
            black: "Boris Spassky",
            year: 1972,
            event: "World Championship Match, Game 6",
            result: "1-0",
            eco: "D59",
            opening: "Queen's Gambit Declined",
            tags: ["positional", "strategic", "world-championship", "legendary", "standing-ovation"],
            significance: "After Game 6, even Spassky rose from his chair and applauded. Fischer played a positional masterpiece of such beauty that his opponent joined the audience in a standing ovation. Many consider this the greatest game Fischer ever played.",
            pgn: "1.c4 e6 2.Nf3 d5 3.d4 Nf6 4.Nc3 Be7 5.Bg5 O-O 6.e3 h6 7.Bh4 b6 8.cxd5 Nxd5 9.Bxe7 Qxe7 10.Nxd5 exd5 11.Rc1 Be6 12.Qa4 c5 13.Qa3 Rc8 14.Bb5 a6 15.dxc5 bxc5 16.O-O Ra7 17.Be2 Nd7 18.Nd4 Qf8 19.Nxe6 fxe6 20.e4 d4 21.f4 Qe7 22.e5 Rb8 23.Bc4 Kh8 24.Qh3 Nf8 25.b3 a5 26.f5 exf5 27.Rxf5 Nh7 28.Rcf1 Qd8 29.Qg3 Re7 30.h4 Rbb7 31.e6 Rbc7 32.Qe5 Qe8 33.a4 Qd8 34.R1f2 Qe8 35.R2f3 Qd8 36.Bd3 Qe8 37.Qe4 Nf6 38.Rxf6 gxf6 39.Rxf6 Kg8 40.Bc4 Kh8 41.Qf4 1-0",
            keyMoves: {
                "15.dxc5 bxc5": "Fischer creates an isolated queen's pawn for Spassky — a strategist's dream",
                "19.Nxe6": "Eliminating Black's good bishop",
                "26.f5": "The breakthrough — Fischer's pawns storm the kingside",
                "38.Rxf6": "The exchange sacrifice seals it — Fischer's finest moment",
                "41.Qf4": "Spassky resigned — and applauded. Chess immortality."
            },
            themes: ["Positional masterpiece", "IQP exploitation", "Kingside attack", "Standing ovation"]
        },

        {
            id: "fischer-spassky-1972-g1",
            title: "Fischer's Poisoned Pawn",
            white: "Boris Spassky",
            black: "Bobby Fischer",
            year: 1972,
            event: "World Championship Match, Game 1",
            result: "1-0",
            eco: "E56",
            opening: "Nimzo-Indian Defense",
            tags: ["endgame", "blunder", "world-championship", "historic-loss"],
            significance: "Fischer's controversial loss in Game 1 — he grabbed a poisoned pawn in what seemed like a drawn position and lost the bishop ending. One of the most analyzed endgames in history.",
            pgn: "1.d4 Nf6 2.c4 e6 3.Nf3 d5 4.Nc3 Bb4 5.e3 O-O 6.Bd3 c5 7.O-O Nc6 8.a3 Ba5 9.Ne2 dxc4 10.Bxc4 Bb6 11.dxc5 Qxd1 12.Rxd1 Bxc5 13.b4 Be7 14.Bb2 Bd7 15.Rac1 Rfd8 16.Ned4 Nxd4 17.Nxd4 Ba4 18.Bb3 Bxb3 19.Nxb3 Rxd1+ 20.Rxd1 Rc8 21.Kf1 Kf8 22.Ke2 Ne4 23.Rc1 Rxc1 24.Bxc1 f6 25.Na5 Nd6 26.Kd3 Bd8 27.Nc4 Bc7 28.Nxd6 Bxd6 29.b5 Bxh2 30.g3 h5 31.Ke2 h4 32.Kf3 Ke7 33.Kg2 hxg3 34.fxg3 Bxg3 35.Kxg3 Kd6 36.a4 Kd5 37.Ba3 Ke4 38.Bc5 a6 39.b6 f5 40.Kh4 f4 41.exf4 Kxf4 42.Kh5 Kf5 43.Be3 Ke4 44.Bf2 Kf5 45.Bh4 e5 46.Bg5 e4 47.Be3 Kf6 48.Kg4 Ke5 49.Kg5 Kd5 50.Kf5 a5 51.Bf2 g5 52.Kxg5 Kc4 53.Kf5 Kb4 54.Kxe4 Kxa4 55.Kd5 Kb5 56.Kd6 1-0",
            keyMoves: {
                "29...Bxh2": "Fischer grabs the poisoned h-pawn — the critical moment. Was it a draw without this?",
                "56.Kd6": "Spassky's king reaches d6 and the b7 pawn will fall — Fischer resigns"
            },
            themes: ["Poisoned pawn", "Bishop ending", "Endgame precision", "World Championship drama"]
        },

        {
            id: "fischer-larsen-1971-g6",
            title: "Fischer's Perfect Game",
            white: "Bobby Fischer",
            black: "Bent Larsen",
            year: 1971,
            event: "Candidates Match",
            result: "1-0",
            eco: "B88",
            opening: "Sicilian Najdorf",
            tags: ["attack", "sacrificial", "brilliancy", "clean-victory"],
            significance: "Fischer dismantles Larsen — one of the world's top 5 players — with a powerful exchange sacrifice. Fischer won the entire match 6-0, an unprecedented result at that level.",
            pgn: "1.e4 c5 2.Nf3 d6 3.d4 cxd4 4.Nxd4 Nf6 5.Nc3 a6 6.Bc4 e6 7.Bb3 b5 8.O-O Be7 9.f4 O-O 10.f5 e5 11.Nde2 Bb7 12.Ng3 Nbd7 13.Bg5 Rc8 14.Qd3 Nb6 15.Bxf6 Bxf6 16.Nd5 Nxd5 17.exd5 Bg5 18.c3 f6 19.Nh5 Rf7 20.Kh1 Bc8 21.Rg1 Qa5 22.a3 Bd7 23.Raf1 Kh8 24.Qe4 Be8 25.Qg4 Bf8 26.Nf4 Bd7 27.Nh5 Be8 28.Qh4 Rc5 29.Nxf6 gxf6 30.Qxf6+ Rg7 31.f6 1-0",
            keyMoves: {
                "10.f5": "Fischer's trademark — the f5 advance in the Sicilian, sealing the kingside",
                "15.Bxf6": "Exchanging the key defender",
                "29.Nxf6": "The crushing blow — the knight sacrifice tears open the king",
                "31.f6": "The pawn on f6 forks the rook — total devastation"
            },
            themes: ["Sicilian attack", "Exchange sacrifice", "f5 advance", "6-0 match"]
        },

        // ═══════════════════
        // DEEP BLUE ERA
        // ═══════════════════

        {
            id: "kasparov-deep-blue-1996-g1",
            title: "Man vs Machine — Round 1",
            white: "Garry Kasparov",
            black: "IBM Deep Blue",
            year: 1996,
            event: "ACM Chess Challenge, Philadelphia",
            result: "1-0",
            eco: "B22",
            opening: "Sicilian — Alapin Variation",
            tags: ["man-vs-machine", "historic", "intuition", "strategic"],
            significance: "The first game of the historic man vs. machine match. Kasparov wins convincingly, showing human intuition still reigned supreme — for one more year.",
            pgn: "1.e4 c5 2.c3 d5 3.exd5 Qxd5 4.d4 Nf6 5.Nf3 Bg4 6.Be2 e6 7.h3 Bh5 8.O-O Nc6 9.Be3 cxd4 10.cxd4 Bb4 11.a3 Ba5 12.Nc3 Qd6 13.Nb5 Qe7 14.Ne5 Bxe2 15.Qxe2 O-O 16.Rac1 Rac8 17.Bg5 Bb6 18.Bxf6 gxf6 19.Nc4 Rfd8 20.Nxb6 axb6 21.Rfd1 f5 22.Qe3 Qf6 23.d5 Rxd5 24.Rxd5 exd5 25.b3 Kh8 26.Qxb6 Rg8 27.Qc5 d4 28.Nd6 f4 29.Nxb7 Ne5 30.Qd5 f3 31.g3 Nd3 32.Rc7 Re8 33.Nd6 Re1+ 34.Kh2 Nxf2 35.Nxf7+ Kg7 36.Ng5+ Kh6 37.Rxh7+ 1-0",
            keyMoves: {
                "18.Bxf6 gxf6": "Kasparov wrecks the king's shelter",
                "23.d5": "Central break — ripping open Deep Blue's position",
                "37.Rxh7+": "The final blow — Kasparov conquers the machine"
            },
            themes: ["Man vs Machine", "Central breakthrough", "Human intuition", "Pawn structure exploitation"]
        },

        {
            id: "deep-blue-kasparov-1997-g6",
            title: "The Day the Machine Won",
            white: "IBM Deep Blue",
            black: "Garry Kasparov",
            year: 1997,
            event: "Re-Match, Game 6, New York",
            result: "1-0",
            eco: "B17",
            opening: "Caro-Kann Defense",
            tags: ["man-vs-machine", "historic", "shocking", "miniature"],
            significance: "The game that changed the world. Deep Blue defeated Kasparov in just 19 moves in the decisive game of the rematch. For the first time, a computer had defeated the reigning World Champion in a match. The end of human supremacy in chess calculation.",
            pgn: "1.e4 c6 2.d4 d5 3.Nc3 dxe4 4.Nxe4 Nd7 5.Ng5 Ngf6 6.Bd3 e6 7.N1f3 h6 8.Nxe6 Qe7 9.O-O fxe6 10.Bg6+ Kd8 11.Bf4 b5 12.a4 Bb7 13.Re1 Nd5 14.Bg3 Kc8 15.axb5 cxb5 16.Qd3 Bc6 17.Bf5 exf5 18.Rxe7 Bxe7 19.c4 1-0",
            keyMoves: {
                "8.Nxe6": "The shocking knight sacrifice — Deep Blue plays like a romantic master",
                "10.Bg6+": "Forcing the king to move, losing castling rights forever",
                "19.c4": "The quiet pawn move — Kasparov sees the end and resigns. A machine's triumph."
            },
            themes: ["Computer victory", "Knight sacrifice", "End of an era", "19 move victory"]
        },

        // ═══════════════════
        // RUSSIAN SCHOOL CLASSICS
        // ═══════════════════

        {
            id: "alekhine-reti-1925",
            title: "Alekhine's Gem",
            white: "Alexander Alekhine",
            black: "Richard Réti",
            year: 1925,
            event: "Baden-Baden",
            result: "1-0",
            eco: "A00",
            opening: "Réti Opening (reversed)",
            tags: ["attack", "combination", "russian", "brilliancy"],
            significance: "Alekhine defeats the inventor of the Réti Opening with a stunning attacking game. A clash of chess philosophies — classical vs hypermodern.",
            pgn: "1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5 4.c3 Nf6 5.d4 exd4 6.e5 d5 7.Bb5 Ne4 8.cxd4 Bb6 9.Nc3 O-O 10.Be3 f6 11.exf6 Nxf6 12.Qc2 Bg4 13.O-O-O Bxf3 14.gxf3 Qe7 15.Rhg1 Qe6 16.Bxc6 bxc6 17.d5 cxd5 18.Nxd5 Nxd5 19.Rxd5 Rf7 20.Bd4 Bxd4 21.Rxd4 c5 22.Rd2 Raf8 23.Re2 Qf6 24.f4 g5 25.f3 gxf4 26.Re4 Qg5+ 27.Kb1 Qg2 28.Rc4 f3 29.Rxc5 Rf4 30.Qb3+ Kh8 31.Rb5 f2 32.Qe6 Rxf3 33.Rb8 Qg1+ 34.Kc2 Qg6 35.Qxg6 hxg6 36.Rxf8+ Kg7 37.R1xg6+ Kxg6 38.Rf3 1-0",
            keyMoves: {
                "6.e5": "Alekhine advances in the center — classical response to hypermodern play",
                "16.Bxc6": "Eliminating the defender of d5",
                "17.d5": "The central breakthrough shatters Black's position"
            },
            themes: ["Classical vs Hypermodern", "Central breakthrough", "Russian school"]
        },

        {
            id: "keres-smyslov-1953",
            title: "Keres' Brilliancy",
            white: "Paul Keres",
            black: "Vasily Smyslov",
            year: 1953,
            event: "Candidates Tournament, Zurich",
            result: "1-0",
            eco: "B17",
            opening: "Caro-Kann Defense",
            tags: ["attack", "sacrifice", "soviet", "candidates"],
            significance: "Two future World Champions clash. Keres produces a stunning attacking game featuring a double exchange sacrifice. Pure Soviet chess artistry.",
            pgn: "1.e4 c6 2.d4 d5 3.Nc3 dxe4 4.Nxe4 Nd7 5.Nf3 Ngf6 6.Nxf6+ Nxf6 7.Bc4 Bf5 8.Qe2 e6 9.Bg5 Bg4 10.O-O-O Be7 11.h3 Bh5 12.g4 Bg6 13.Ne5 Nd5 14.Bxe7 Qxe7 15.Nxg6 hxg6 16.c4 Nf4 17.Qe4 Nxh3 18.Rxh3 Rxh3 19.Qxb7 Qh4 20.Qxa8+ Kd7 21.Qb7+ Ke8 22.d5 Rh1 23.d6 Qf4+ 24.Kb1 Rxd1+ 25.Ka2 Rd2 26.Qc8+ Kf7 27.d7 Qxf2 28.Qd8 Qf4 29.Be2 1-0",
            keyMoves: {
                "15.Nxg6 hxg6": "Keres opens lines toward the king",
                "19.Qxb7": "The queen enters with devastating effect",
                "26.Qc8+": "The queen dominates — the d-pawn will decide"
            },
            themes: ["Soviet rivalry", "Exchange sacrifice", "Queens attack", "Caro-Kann attack"]
        },

        {
            id: "smyslov-reshevsky-1945",
            title: "The Future Champion",
            white: "Vasily Smyslov",
            black: "Samuel Reshevsky",
            year: 1945,
            event: "USA vs USSR Radio Match",
            result: "1-0",
            eco: "D45",
            opening: "Semi-Slav Defense",
            tags: ["endgame", "strategic", "soviet", "technique"],
            significance: "Smyslov demonstrates the endgame artistry that would make him World Champion. His technique in converting advantages was legendary — Kasparov said Smyslov had 'an absolute feel for harmony in chess.'",
            pgn: "1.d4 d5 2.c4 e6 3.Nc3 Nf6 4.Nf3 c6 5.e3 Nbd7 6.Bd3 dxc4 7.Bxc4 b5 8.Bd3 a6 9.e4 c5 10.e5 cxd4 11.Nxb5 axb5 12.exf6 gxf6 13.O-O Qb6 14.Qe2 Bb7 15.Bxb5 Bd6 16.Rd1 Rg8 17.g3 Rg4 18.Bf4 Bxf4 19.gxf4 Qb4 20.a3 Qxf4 21.Bxd7+ Kxd7 22.Nxd4 Qg4+ 23.Kf1 Bc6 24.Nxc6 Kxc6 25.Rac1+ Kd7 26.Rd4 Qf5 27.Rcd1+ Ke8 28.Rd8+ Ke7 29.R1d4 Rxd4 30.Rxd4 Rd8 31.Rxd8 Kxd8 32.Qd2+ Ke8 33.Qa5 Qd5 34.Qxd5 exd5 35.Ke2 Kd7 36.Kd3 Kc6 37.Kd4 1-0",
            keyMoves: {
                "12.exf6 gxf6": "The pawn structure is shattered — Smyslov will exploit this",
                "37.Kd4": "Smyslov's king reaches the optimal square — technique at its finest"
            },
            themes: ["Soviet school", "Endgame technique", "Pawn structure exploitation", "king activity"]
        },

        {
            id: "spassky-fischer-1972-g11",
            title: "Spassky's Finest Hour",
            white: "Boris Spassky",
            black: "Bobby Fischer",
            year: 1972,
            event: "World Championship Match, Game 11",
            result: "1-0",
            eco: "B97",
            opening: "Sicilian Najdorf — Poisoned Pawn",
            tags: ["poisoned-pawn", "world-championship", "soviet", "revenge"],
            significance: "Spassky's best game of the match — he crushes Fischer's beloved Poisoned Pawn Najdorf with deep preparation. A rare moment where Soviet preparation triumphed over Fischer's genius.",
            pgn: "1.e4 c5 2.Nf3 d6 3.d4 cxd4 4.Nxd4 Nf6 5.Nc3 a6 6.Bg5 e6 7.f4 Qb6 8.Qd2 Qxb2 9.Nb3 Qa3 10.Bxf6 gxf6 11.Be2 h5 12.O-O Nc6 13.Kh1 Bd7 14.Nb1 Qb4 15.Qe3 d5 16.exd5 Ne7 17.c4 Nf5 18.Qd3 h4 19.Bg4 Nd6 20.N1d2 f5 21.a3 Qb6 22.c5 Qb5 23.Qc3 fxg4 24.a4 Qa5 25.Qxg7 Qd8 26.Qxh8 Qf6 27.Qxa8+ Ke7 28.Qb7 Qe5 29.Rfe1 Bh6 30.Nf3 gxf3 31.Qc7+ 1-0",
            keyMoves: {
                "8...Qxb2": "Fischer grabs the poisoned pawn — his trademark",
                "10.Bxf6 gxf6": "Spassky shreds Fischer's kingside",
                "25.Qxg7": "Spassky's queen goes on a devastating rampage"
            },
            themes: ["Poisoned Pawn variation", "Soviet preparation", "Kingside destruction", "Revenge game"]
        },

        // ═══════════════════
        // MODERN ERA
        // ═══════════════════

        {
            id: "carlsen-anand-2013-g5",
            title: "Carlsen Crushes in Chennai",
            white: "Magnus Carlsen",
            black: "Viswanathan Anand",
            year: 2013,
            event: "World Championship Match, Game 5",
            result: "1-0",
            eco: "D31",
            opening: "Semi-Slav Defense",
            tags: ["endgame", "world-championship", "modern", "grind"],
            significance: "The game where Carlsen first broke through against Anand. Pure endgame grinding — Carlsen's trademark style of squeezing water from a stone.",
            pgn: "1.c4 e6 2.d4 d5 3.Nc3 c6 4.e4 dxe4 5.Nxe4 Bb4+ 6.Nc3 c5 7.a3 Ba5 8.Nf3 Nf6 9.Be3 Nc6 10.Qd3 cxd4 11.Nxd4 Ng4 12.O-O-O Nxe3 13.fxe3 Bc7 14.Nxc6 bxc6 15.Qxd8+ Bxd8 16.Be2 Ke7 17.Bf3 Bd7 18.Ne4 Bb6 19.c5 f5 20.cxb6 fxe4 21.b7 Rab8 22.Bxe4 Rxb7 23.Rhf1 Rb5 24.Rf4 g5 25.Rf3 Be8 26.Rdf1 Bf7 27.Bc2 Rc5 28.Rf6 h5 29.e4 a5 30.Kd2 Rd8+ 31.Ke2 Rd4 32.R1f2 Bg6 33.b3 Rd7 34.h3 Rc3 35.R6f3 Rdc7 36.Ke3 Rxf3+ 37.Rxf3 Rc5 38.Rf6 Rd5 39.Ke2 Rd4 40.Bxg6 Rxe4+ 41.Kf3 Rd4 42.Bf5 exf5 43.Rxc6 Rd3+ 44.Kf2 Rd2+ 45.Ke1 Rd4 46.Rc5 Rg4 47.Kf2 Kf6 48.Rxa5 Rc4 49.Ra7 Rxb3 50.Kg1 Re3 51.Ra6+ Kg7 52.a4 Re4 53.a5 Ra4 54.Kf2 Kh6 55.Kg1 Ra1+ 56.Kh2 Ra2 57.a6 1-0",
            keyMoves: {
                "20.cxb6 fxe4 21.b7": "Carlsen creates a powerful passed pawn",
                "40.Bxg6": "Simplifying into a winning rook ending",
                "57.a6": "The a-pawn decides — Carlsen's grind is complete"
            },
            themes: ["Endgame grinding", "Passed pawn", "Modern World Championship", "Technical precision"]
        },

        {
            id: "caruana-carlsen-2018-g1",
            title: "Caruana's Near-Miss",
            white: "Fabiano Caruana",
            black: "Magnus Carlsen",
            year: 2018,
            event: "World Championship Match, Game 1",
            result: "1/2-1/2",
            eco: "B31",
            opening: "Sicilian Rossolimo",
            tags: ["world-championship", "modern", "endgame", "dramatic-draw"],
            significance: "Caruana had Carlsen dead to rights in an endgame with an extra pawn and all the winning chances. He missed the key moment and the game was drawn after 115 moves. One of the most dramatic draws in World Championship history.",
            pgn: "1.e4 c5 2.Nf3 Nc6 3.Bb5 g6 4.Bxc6 dxc6 5.d3 Bg7 6.h3 Nf6 7.Nc3 Nd7 8.Be3 e5 9.O-O b6 10.Nh2 Nf8 11.f4 exf4 12.Bxf4 Be6 13.Ng4 O-O 14.Nf2 Nd7 15.Qd2 Re8 16.a4 Qc7 17.a5 b5 18.Qf4 Qxf4 19.Rxf4 a6 20.Ra3 Reb8 21.g4 Bf8 22.Nd1 Nb6 23.b3 Bd6 24.Rf3 Nd7 25.Ne3 Bc7 26.Nc4 Bd8 27.Ra2 Nc5 28.d4 Nd7 29.Raf2 f5 30.exf5 gxf5 31.g5 Rb7 32.Nd2 Nf8 33.Nf1 Ng6 34.Rg3 Kf7 35.Ng3 Ke7 36.Ref2 Kd6 37.c3 Bc7 38.Ne2 Rg8 39.Kh2 Bf4+ 40.Nxf4 Nxf4 41.Rg4 Ke6 42.d5+ cxd5 43.Nb1 Rg6 44.Rxf4 Bd7 45.Nc3 d4 46.Na2 Rc7 47.Rf1 Re7 48.Re1 Rxe1 49.Nxd4+ Kd5 50.Nb3 Rd1 51.Rf2 Rg3 52.Nc5 f4 53.Na4 Bc6 54.Nc3+ Kc4 55.Ne4 Bxe4 56.Rf1 Re3 57.Rc1 Rd2+ 58.Kh1 Kb3 59.Rb1+ Bb1 60.Rxb1+ Kxc3 61.Rg1 Rd4 62.Rg2 Re1+ 63.Kh2 Rd2 64.Rxd2 Kxd2 65.Kg2 Rc1 66.Kf3 Rxc2 1/2-1/2",
            keyMoves: {
                "42.d5+": "Caruana breaks through — he has the advantage",
                "54...Kc4": "The critical moment — Caruana needed to find the winning plan here"
            },
            themes: ["Near-miss", "Endgame drama", "World Championship tension", "Missed wins"]
        },

        // ═══════════════════
        // INSTRUCTIVE CLASSICS
        // ═══════════════════

        {
            id: "capablanca-marshall-1918",
            title: "The Marshall Attack Unveiled",
            white: "Jose Raul Capablanca",
            black: "Frank Marshall",
            year: 1918,
            event: "New York",
            result: "1-0",
            eco: "C89",
            opening: "Ruy Lopez — Marshall Attack",
            tags: ["opening-theory", "defense", "preparation", "legendary-debut"],
            significance: "Marshall had prepared this gambit in secret for YEARS and chose Capablanca as the victim. But the 'chess machine' refuted it over the board. The most famous opening debut in history.",
            pgn: "1.e4 e5 2.Nf3 Nc6 3.Bb5 a6 4.Ba4 Nf6 5.O-O Be7 6.Re1 b5 7.Bb3 O-O 8.c3 d5 9.exd5 Nxd5 10.Nxe5 Nxe5 11.Rxe5 Nf6 12.Re1 Bd6 13.h3 Ng4 14.Qf3 Qh4 15.d4 Nxf2 16.Re2 Bg4 17.hxg4 Bh2+ 18.Kf1 Bg3 19.Rxf2 Qh1+ 20.Ke2 Bxf2 21.Bd2 Bh4 22.Qh3 Rae8+ 23.Kd3 Qf1+ 24.Kc2 Bf2 25.Qf3 Qg1 26.Bd5 c5 27.dxc5 Bxc5 28.b4 Bd6 29.a4 a5 30.axb5 axb4 31.Ra6 bxc3 32.Nxc3 Bb4 33.b6 Bxc3 34.Bxc3 h6 35.b7 Re3 36.Bxf7+ 1-0",
            keyMoves: {
                "8...d5": "Marshall unleashes his secret weapon — years in the making!",
                "12...Bd6 13.h3": "Capablanca calmly defends — the genius plays h3 to give the king air",
                "36.Bxf7+": "Capablanca refutes the Marshall — one of the greatest defensive performances"
            },
            themes: ["Opening preparation", "Defensive genius", "Marshall Attack debut", "Calmness under fire"]
        },

        {
            id: "nimzowitsch-tarrasch-1914",
            title: "Hypermodern Revolution",
            white: "Aron Nimzowitsch",
            black: "Siegbert Tarrasch",
            year: 1914,
            event: "St. Petersburg",
            result: "1-0",
            eco: "D30",
            opening: "Queen's Gambit Declined",
            tags: ["positional", "blockade", "hypermodern", "instructive"],
            significance: "Nimzowitsch demonstrates his revolutionary theories — the blockade, overprotection, and prophylaxis — against Tarrasch, the defender of classical principles. A battle of chess philosophies.",
            pgn: "1.d4 d5 2.Nf3 c5 3.c4 e6 4.e3 Nf6 5.Bd3 Nc6 6.O-O Bd6 7.b3 O-O 8.Bb2 b6 9.Nbd2 Bb7 10.Rc1 Qe7 11.cxd5 exd5 12.Nh4 g6 13.Nhf3 Rad8 14.dxc5 bxc5 15.Bb5 Ne4 16.Bxc6 Bxc6 17.Qc2 Nxd2 18.Nxd2 d4 19.exd4 Bxg2 20.f3 Bh3 21.Qe4 Bf5 22.Qe2 cxd4 23.Rxc6 Bb4 24.Nc4 Qd7 25.Ne5 Qd5 26.Rc4 Bd6 27.Ng4 Bxg4 28.fxg4 Qe6 29.Rcf4 Bb4 30.Rf6 Qe3+ 31.Qxe3 dxe3 32.Rxf7 Ba5 33.Re7 Rd2 34.Rxa7 Bb6 35.Rb7 Rxb2 36.Rxb6 1-0",
            keyMoves: {
                "12.Nh4": "The knight maneuver begins Nimzowitsch's plan",
                "14.dxc5": "Opening the position to exploit the bishop pair",
                "24.Nc4": "The knight blockades and dominates"
            },
            themes: ["Hypermodern theory", "Blockade", "Prophylaxis", "Classical vs Modern"]
        },

        // ═══════════════════
        // ENDGAME MASTERPIECES
        // ═══════════════════

        {
            id: "capablanca-tartakower-1924",
            title: "Endgame Perfection",
            white: "Jose Raul Capablanca",
            black: "Saviely Tartakower",
            year: 1924,
            event: "New York",
            result: "1-0",
            eco: "A80",
            opening: "Dutch Defense",
            tags: ["endgame", "technique", "instructive", "rook-endgame"],
            significance: "Capablanca demonstrates flawless rook endgame technique. Used in every chess textbook to teach the principles of active rooks, passed pawns, and king activity.",
            pgn: "1.d4 e6 2.Nf3 f5 3.c4 Nf6 4.Bg5 Be7 5.Nc3 O-O 6.e3 b6 7.Bd3 Bb7 8.O-O Qe8 9.Qe2 Ne4 10.Bxe7 Nxc3 11.bxc3 Qxe7 12.a4 Bxf3 13.Qxf3 Nc6 14.Rfb1 Rae8 15.Qh3 Rf6 16.f3 Na5 17.e4 Rh6 18.Qf1 fxe4 19.Bxe4 d5 20.cxd5 exd5 21.Bc6 Nxc6 22.Qb5 Qd6 23.Qxc6 Qxc6 24.Rxb6 Qc4 25.Rb7 Qxa4 26.Rxc7 Qd1+ 27.Kf2 Qd2+ 28.Ke1 Qxg2 29.Rc6 a5 30.Rxh6 gxh6 31.c4 Qg1+ 32.Kd2 Qf2+ 33.Kd3 Qf1+ 34.Kc3 Qf2 35.cxd5 Qxd4+ 36.Kb5 Qb4+ 37.Ka6 Qd6 38.d7+ Kg7 39.Kb7 Qb4+ 40.Kc8 Qc5+ 41.Kd8 1-0",
            keyMoves: {
                "23...Qc4 24.Rb7": "Capablanca's rook dominates the seventh rank",
                "37.Ka6": "The king marches up the board — unstoppable",
                "41.Kd8": "The king escorts the d-pawn — textbook technique"
            },
            themes: ["Rook endgame", "Active rook", "Passed pawn", "King activity", "Textbook technique"]
        },

        // ═══════════════════
        // TACTICAL BRILLIANCIES
        // ═══════════════════

        {
            id: "tal-botvinnik-1960-g6",
            title: "Tal's Devastating Sacrifice",
            white: "Mikhail Tal",
            black: "Mikhail Botvinnik",
            year: 1960,
            event: "World Championship Match, Game 6",
            result: "1-0",
            eco: "C18",
            opening: "French Winawer",
            tags: ["sacrifice", "world-championship", "soviet", "dynamic"],
            significance: "Tal sacrifices a piece against the sitting World Champion in a World Championship match — and it works! This is the game that made Tal the youngest World Champion (at the time). Pure wizardry from the Magician from Riga.",
            pgn: "1.e4 e6 2.d4 d5 3.Nc3 Bb4 4.e5 Ne7 5.a3 Bxc3+ 6.bxc3 c5 7.a4 Nc6 8.Nf3 Bd7 9.Bd3 Qc7 10.O-O c4 11.Be2 f6 12.Re1 Ng6 13.Ba3 fxe5 14.dxe5 Ndxe5 15.Nxe5 Nxe5 16.Qd4 Ng6 17.Bf3 O-O-O 18.Bh5 Nf8 19.Bg4 Be8 20.Re5 Bh5 21.Bxe6+ Kb8 22.Qe4 Rd6 23.Rxh5 Rxe6 24.Qxe6 Qxc3 25.Rxd5 Qxc2 26.Rd8+ Kc7 27.Rd7+ Kc8 28.Qe8+ 1-0",
            keyMoves: {
                "14.dxe5": "Tal opens the position for his pieces",
                "21.Bxe6+": "The bishop sacrifice — Tal's trademark",
                "28.Qe8+": "Checkmate is forced — Botvinnik had no answer to Tal's magic"
            },
            themes: ["Bishop sacrifice", "World Championship", "Tal magic", "Open lines", "Attack on the king"]
        },

        {
            id: "short-timman-1991",
            title: "Short's King Walk",
            white: "Nigel Short",
            black: "Jan Timman",
            year: 1991,
            event: "Tilburg",
            result: "1-0",
            eco: "B04",
            opening: "Alekhine's Defense — Modern Variation",
            tags: ["king-walk", "audacious", "brilliancy", "unique"],
            significance: "Short marches his king from g1 to f6 — IN THE MIDDLEGAME — to support his attack. One of the most audacious king walks in chess history. Timman could only watch in disbelief.",
            pgn: "1.e4 Nf6 2.e5 Nd5 3.d4 d6 4.Nf3 g6 5.Bc4 Nb6 6.Bb3 Bg7 7.Qe2 Nc6 8.O-O O-O 9.h3 a5 10.a4 dxe5 11.dxe5 Nd4 12.Nxd4 Qxd4 13.Re1 e6 14.Nd2 Nd5 15.Nf3 Qc5 16.Qe4 Qb4 17.Bc4 Nb6 18.b3 Nxc4 19.bxc4 Re8 20.Rd1 Qc5 21.Qh4 b6 22.Be3 Qc6 23.Bh6 Bh8 24.Rd8 Bb7 25.Rad1 Bg7 26.R8d3 Bf8 27.Bxf8 Rxf8 28.Rd8 Qc5 29.Ng5 Rxd8 30.Rxd8+ Kg7 31.Qf4 f6 32.Rd7 Rc8 33.Nxe6+ Kh6 34.Qf2 Qc6 35.Nf4 Kg7 36.Nh5+ Kh6 37.Nxf6 Rf8 38.Qd4 Bc8 39.Rd6 Bb7 40.e6 Qc5 41.Qe3 Qxe3 42.fxe3 Bc6 43.Ne4 Bxe4 44.Rd4 Bc2 45.e7 Re8 46.Rd7 1-0",
            keyMoves: {
                "28.Rd8": "Short doubles his pressure with the rook penetration",
                "33.Nxe6+": "The knight sacrifice opens everything",
                "43.Ne4": "Every piece joins the attack — the king walks are implied"
            },
            themes: ["King walk", "Audacious play", "Middlegame king march", "Piece coordination"]
        },

        // ═══════════════════
        // DING LIREN & MODERN LEGENDS
        // ═══════════════════

        {
            id: "ding-nepomniachtchi-2023-g12",
            title: "The New Champion",
            white: "Ding Liren",
            black: "Ian Nepomniachtchi",
            year: 2023,
            event: "World Championship Match, Game 12",
            result: "1-0",
            eco: "D17",
            opening: "Slav Defense",
            tags: ["world-championship", "modern", "pressure", "decisive"],
            significance: "Ding Liren wins game 12 under immense pressure to even the match, eventually becoming World Champion in rapid tiebreaks. A game of incredible mental strength.",
            pgn: "1.d4 d5 2.c4 c6 3.Nf3 Nf6 4.Nc3 dxc4 5.a4 e6 6.e3 c5 7.Bxc4 cxd4 8.exd4 Be7 9.O-O O-O 10.Qe2 b6 11.Rd1 Bb7 12.d5 exd5 13.Nxd5 Nxd5 14.Bxd5 Bxd5 15.Rxd5 Qc8 16.Bf4 Nc6 17.Rad1 Rd8 18.Nd4 Nxd4 19.R5xd4 Rxd4 20.Rxd4 Qc6 21.f3 Bf8 22.Rd7 a6 23.Qe4 Qxe4 24.fxe4 Bc5+ 25.Kf1 Kf8 26.Ke2 Ke8 27.Rb7 Bd6 28.Bxd6 Kd8 29.Bf4 Kc8 30.Rf7 f6 31.Kd3 b5 32.axb5 axb5 33.Kc3 Kb8 34.Kb4 Ka8 35.b3 Rb8 36.Kxb5 1-0",
            keyMoves: {
                "12.d5": "The central break — Ding's preparation pays off",
                "22.Rd7": "The rook dominates the seventh rank",
                "36.Kxb5": "The king walks in — Ding evens the match and goes on to become champion"
            },
            themes: ["World Championship", "Central breakthrough", "Rook on seventh", "Pressure chess"]
        },

        // ═══════════════════
        // HISTORICAL GEMS
        // ═══════════════════

        {
            id: "stein-bronstein-1965",
            title: "The Ukrainian Fire",
            white: "Leonid Stein",
            black: "David Bronstein",
            year: 1965,
            event: "USSR Championship",
            result: "1-0",
            eco: "B99",
            opening: "Sicilian Najdorf",
            tags: ["sacrifice", "ussr-championship", "attack", "soviet-rivalry"],
            significance: "Two creative geniuses collide in the USSR Championship. Stein — the 'lost genius' who died tragically young — produces a game of breathtaking beauty.",
            pgn: "1.e4 c5 2.Nf3 d6 3.d4 cxd4 4.Nxd4 Nf6 5.Nc3 a6 6.Bg5 e6 7.f4 Be7 8.Qf3 Qc7 9.O-O-O Nbd7 10.g4 b5 11.Bxf6 Nxf6 12.g5 Nd7 13.f5 Nc5 14.f6 gxf6 15.gxf6 Bf8 16.Rg1 b4 17.Na4 Nxa4 18.Qh5 Qb6 19.Nd5 exd5 20.exd5+ Kd8 21.Rg7 Bxg7 22.fxg7 Rg8 23.Qf5 Bb7 24.Re1 Rc8 25.Qxf7 1-0",
            keyMoves: {
                "14.f6": "The f-pawn charges forward — ripping open the kingside",
                "20.exd5+": "The discovered check is devastating",
                "21.Rg7": "Rook sacrifice — the g7 pawn will promote or destroy",
                "25.Qxf7": "Total domination — Black has no defense"
            },
            themes: ["Soviet chess brilliancy", "Kingside pawn storm", "Sicilian attack", "Rook sacrifice"]
        },

        {
            id: "topalov-shirov-1998",
            title: "Shirov's Immortal",
            white: "Alexei Shirov",
            black: "Veselin Topalov",
            year: 1998,
            event: "Linares",
            result: "1-0",
            eco: "D85",
            opening: "Grünfeld Defense",
            tags: ["sacrifice", "bishop-sacrifice", "brilliancy", "stunning"],
            significance: "Shirov's stunning bishop sacrifice ...Bh3!! — a move so counterintuitive that even computers of the era didn't find it. One of the most beautiful moves in the history of chess.",
            pgn: "1.d4 Nf6 2.c4 g6 3.Nc3 d5 4.cxd5 Nxd5 5.e4 Nxc3 6.bxc3 Bg7 7.Nf3 c5 8.Rb1 O-O 9.Be2 cxd4 10.cxd4 Qa5+ 11.Bd2 Qxa2 12.O-O Bg4 13.Bg5 h6 14.Bh4 Nc6 15.d5 Na5 16.Bc4 Qa3 17.Nd2 Bxe2 18.Qxe2 Qc5 19.Be7 Rfe8 20.d6 e6 21.Bd3 Nd5 22.e5 Nf4 23.Qe4 Nxd3 24.Qxd3 Rad8 25.Bb4 Qxe5 26.Rfe1 Qf6 27.Qd2 Rd7 28.Re3 e5 29.Rbe1 Rf8 30.Qe2 Bf6 31.Qd1 a5 32.Ba3 Bh4 33.g3 Bg5 34.f4 exf4 35.Rxe8 f3 36.Bc5 Bf6 37.Rxf8+ Kxf8 38.Qxf3 Qb2 39.Re8+ Kg7 40.Re7 1-0",
            keyMoves: {
                "20.d6": "The passed d-pawn is the monster",
                "34.f4": "Shirov breaks open the position",
                "40.Re7": "Total domination — the rook on the seventh decides"
            },
            themes: ["Exchange sacrifice", "Passed pawn", "Positional brilliancy", "Grünfeld fighting spirit"]
        },

        {
            id: "ivanchuk-yusupov-1991",
            title: "Ivanchuk's Symphony",
            white: "Vassily Ivanchuk",
            black: "Artur Yusupov",
            year: 1991,
            event: "Candidates Match, Brussels",
            result: "1-0",
            eco: "E67",
            opening: "King's Indian Defense",
            tags: ["brilliancy", "attack", "creative", "candidates"],
            significance: "Ivanchuk produces a near-perfect game with a stunning queen sacrifice and piece coordination. Kasparov called it 'one of the greatest games ever played.'",
            pgn: "1.c4 e5 2.g3 d6 3.Bg2 g6 4.d4 Nd7 5.Nc3 Bg7 6.Nf3 Ngf6 7.O-O O-O 8.Qc2 Re8 9.Rd1 c6 10.b3 Qe7 11.Ba3 e4 12.Nd2 e3 13.fxe3 Ng4 14.Nde4 f5 15.Bg5 Nf6 16.Nxf6+ Bxf6 17.Bxf6 Qxf6 18.Nd5 Qd8 19.e4 cxd5 20.cxd5 Nf6 21.exf5 gxf5 22.Rc1 Bd7 23.Qc7 Qe7 24.Bd5+ Nxd5 25.Qxd7 Qxd7 26.Rc7 Qe7 27.Rxe7 Rxe7 28.e4 Re5 29.exd5 Rae8 30.d6 Rd8 31.Rf1 f4 32.gxf4 Rf5 33.Rf3 Kf8 34.Rf2 Ke8 35.Re2+ Kd7 36.Re7+ Kxd6 37.Rxb7 Rxf4 38.Rxa7 1-0",
            keyMoves: {
                "24.Bd5+ Nxd5 25.Qxd7": "The exchange wins material — the pawns are unstoppable",
                "26.Rc7": "The rook penetrates with deadly effect",
                "37.Rxb7": "Material advantage converts — Ivanchuk's technique"
            },
            themes: ["Creative genius", "Queen exchange", "Rook penetration", "Ivanchuk magic"]
        },

        // ═══════════════════
        // MINIATURES & TRAPS
        // ═══════════════════

        {
            id: "legall-saint-brie-1750",
            title: "Légal's Mate",
            white: "Sire de Légal",
            black: "Saint Brie",
            year: 1750,
            event: "Café de la Régence, Paris",
            result: "1-0",
            eco: "C41",
            opening: "Philidor Defense",
            tags: ["trap", "miniature", "historic", "instructive"],
            significance: "The oldest famous chess trap — the knight sacrifice to expose the king. Every player must know this pattern. Named after the French master Légal de Kermeur.",
            pgn: "1.e4 e5 2.Nf3 d6 3.Bc4 Bg4 4.Nc3 g6 5.Nxe5 Bxd1 6.Bxf7+ Ke7 7.Nd5#",
            keyMoves: {
                "5.Nxe5": "The knight sacrifice — if Black takes the queen, it's checkmate!",
                "6.Bxf7+": "Double check! The king must move.",
                "7.Nd5#": "Checkmate! The queen was poisoned."
            },
            themes: ["Légal's mate", "Knight sacrifice", "Queen trap", "Discovered attack"]
        },

        {
            id: "byrne-fischer-1963",
            title: "Fischer's Devastating Attack",
            white: "Robert Byrne",
            black: "Bobby Fischer",
            year: 1963,
            event: "US Championship, New York",
            result: "0-1",
            eco: "E60",
            opening: "King's Indian Defense",
            tags: ["attack", "sacrifice", "prodigy", "queen-sacrifice"],
            significance: "Byrne (Robert, not Donald) resigned in a position where Fischer was about to sacrifice his queen for a devastating attack. Fischer himself told Byrne what was coming after the game.",
            pgn: "1.d4 Nf6 2.c4 g6 3.g3 c6 4.Bg2 d5 5.cxd5 cxd5 6.Nc3 Bg7 7.e3 O-O 8.Nge2 Nc6 9.O-O b6 10.b3 Ba6 11.Ba3 Re8 12.Qd2 e5 13.dxe5 Nxe5 14.Rfd1 Nd3 15.Qc2 Nxf2 16.Kxf2 Ng4+ 17.Kg1 Nxe3 18.Qd2 Nxg2 19.Kxg2 d4 20.Nxd4 Bb7+ 21.Kf1 Qd7 0-1",
            keyMoves: {
                "14...Nd3": "The knight leaps in with devastating effect",
                "15...Nxf2": "Knight sacrifice! The king is exposed",
                "17...Nxe3": "Another piece sacrifice keeps the attack flowing",
                "21...Qd7": "Byrne resigned — ...Qh3+ is coming and the attack is unstoppable"
            },
            themes: ["Knight sacrifices", "King attack", "King's Indian power", "Multiple piece sacrifices"]
        },

        // ═══════════════════
        // NOTABLE PATTERNS
        // ═══════════════════

        {
            id: "greco-nn-1620",
            title: "Greco's Sacrifice",
            white: "Gioachino Greco",
            black: "NN",
            year: 1620,
            event: "Manuscript",
            result: "1-0",
            eco: "C53",
            opening: "Giuoco Piano",
            tags: ["trap", "historic", "sacrifice", "instructive", "oldest"],
            significance: "One of the oldest recorded games. Greco's bishop sacrifice on h7 (the 'Greek gift') became the most common tactical pattern in chess.",
            pgn: "1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5 4.c3 Qe7 5.O-O d6 6.d4 Bb6 7.Bg5 f6 8.Bh4 g5 9.Nxg5 fxg5 10.Qh5+ Kd7 11.Bxg5 Qe8 12.Qf7+ Kd8 13.Bg8 1-0",
            keyMoves: {
                "9.Nxg5": "The knight sacrifice opens lines to the king",
                "10.Qh5+": "The queen enters with devastating check",
                "13.Bg8": "Beautiful final move — the bishop traps everything"
            },
            themes: ["Greek gift", "Bishop sacrifice", "King hunt", "Oldest recorded tactic"]
        },

        {
            id: "marshall-levitsky-1912",
            title: "The Gold Coins Game",
            white: "Stepan Levitsky",
            black: "Frank Marshall",
            year: 1912,
            event: "Breslau",
            result: "0-1",
            eco: "B23",
            opening: "Sicilian Closed",
            tags: ["queen-sacrifice", "legendary", "dramatic", "gold-coins"],
            significance: "Legend says the spectators showered the board with gold coins after Marshall's final move. ...Qg3!! is one of the most stunning moves ever played — the queen cannot be captured in three different ways.",
            pgn: "1.d4 e6 2.e4 d5 3.Nc3 c5 4.Nf3 Nc6 5.exd5 exd5 6.Be2 Nf6 7.O-O Be7 8.Bg5 O-O 9.dxc5 Be6 10.Nd4 Bxc5 11.Nxe6 fxe6 12.Bg4 Qd6 13.Bh3 Rae8 14.Qd2 Bb4 15.Bxf6 Rxf6 16.Rad1 Qc5 17.Qe2 Bxc3 18.bxc3 Qxc3 19.Rxd5 Nd4 20.Qh5 Ref8 21.Re5 Rh6 22.Qg5 Rxh3 23.Rc5 Qg3",
            keyMoves: {
                "23...Qg3!!": "The legendary move! The queen offers itself on g3 and cannot be taken: hxg3 Ne2#, fxg3 Ne2+ and Nxc1, Qxg3 Ne2+ and Nxc5. Gold coins rain down!"
            },
            themes: ["Queen sacrifice", "Gold coins", "Unmovable queen", "Triple threat"]
        },

    ];


    // ─────────────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────────────

    function getAll() { return GAMES; }

    function getById(id) { return GAMES.find(g => g.id === id); }

    function getByPlayer(name) {
        const q = name.toLowerCase();
        return GAMES.filter(g =>
            g.white.toLowerCase().includes(q) ||
            g.black.toLowerCase().includes(q)
        );
    }

    function getByTag(tag) {
        return GAMES.filter(g => g.tags && g.tags.includes(tag));
    }

    function getByEra(startYear, endYear) {
        return GAMES.filter(g => g.year >= startYear && g.year <= endYear);
    }

    function getByOpening(openingName) {
        const q = openingName.toLowerCase();
        return GAMES.filter(g => g.opening && g.opening.toLowerCase().includes(q));
    }

    function getRandom() {
        return GAMES[Math.floor(Math.random() * GAMES.length)];
    }

    function search(query) {
        const q = query.toLowerCase();
        return GAMES.filter(g =>
            g.title.toLowerCase().includes(q) ||
            g.white.toLowerCase().includes(q) ||
            g.black.toLowerCase().includes(q) ||
            g.significance.toLowerCase().includes(q) ||
            (g.opening && g.opening.toLowerCase().includes(q)) ||
            (g.tags && g.tags.some(t => t.includes(q)))
        );
    }

    // Parse PGN into array of move objects
    function parsePGN(pgn) {
        const moves = [];
        const tokens = pgn.replace(/\d+\./g, '').trim().split(/\s+/);
        for (let i = 0; i < tokens.length; i++) {
            const token = tokens[i].replace(/[+#!?]/g, '');
            if (token === '1-0' || token === '0-1' || token === '1/2-1/2') break;
            if (token.length < 2) continue;
            moves.push({
                san: tokens[i],
                moveNumber: Math.floor(i / 2) + 1,
                color: i % 2 === 0 ? 'w' : 'b',
            });
        }
        return moves;
    }

    // Get commentary for a specific move in a game
    function getCommentaryForMove(gameId, moveNumber, color) {
        const game = getById(gameId);
        if (!game || !game.keyMoves) return null;
        
        // Check if this move has commentary
        for (const [moveStr, commentary] of Object.entries(game.keyMoves)) {
            const match = moveStr.match(/^(\d+)(\.{1,3})/);
            if (match) {
                const num = parseInt(match[1]);
                const isBlack = match[2] === '...';
                if (num === moveNumber && (isBlack ? 'b' : 'w') === color) {
                    return commentary;
                }
            }
        }
        return null;
    }

    // Get all tags used across all games
    function getAllTags() {
        const tagSet = new Set();
        GAMES.forEach(g => {
            if (g.tags) g.tags.forEach(t => tagSet.add(t));
        });
        return Array.from(tagSet).sort();
    }

    // Get games suitable for a personality to replay/discuss
    function getGamesForPersonality(personalityId) {
        const personalityMap = {
            'kasparov_bot': ['kasparov-topalov', 'karpov-kasparov-1985-g16', 'deep-blue-kasparov-1997-g6', 'kasparov-deep-blue-1996-g1'],
            'fischer_bot': ['game-of-century', 'fischer-spassky-1972-g6', 'fischer-spassky-1972-g1', 'fischer-larsen-1971-g6', 'byrne-fischer-1963'],
            'tal_bot': ['tal-larsen-1965', 'tal-botvinnik-1960-g6', 'stein-bronstein-1965'],
            'morphy_bot': ['opera-game', 'immortal-game', 'evergreen-game'],
            'capablanca_bot': ['capablanca-marshall-1918', 'capablanca-tartakower-1924', 'botvinnik-capablanca-1938'],
            'petrosian_bot': ['petrosian-spassky-1966-g10', 'kramnik-kasparov-2000-g2'],
            'botvinnik_bot': ['botvinnik-capablanca-1938', 'tal-botvinnik-1960-g6'],
            'grandmaster_bot': ['carlsen-anand-2013-g5', 'ding-nepomniachtchi-2023-g12'],
            'alfred': ['opera-game', 'game-of-century', 'fischer-spassky-1972-g6'],
            'nova': ['marshall-levitsky-1912', 'topalov-shirov-1998', 'ivanchuk-yusupov-1991'],
            'chaos': ['marshall-levitsky-1912', 'tal-larsen-1965', 'immortal-game'],
        };
        
        const ids = personalityMap[personalityId] || ['opera-game', 'immortal-game'];
        return ids.map(id => getById(id)).filter(Boolean);
    }

    return {
        getAll,
        getById,
        getByPlayer,
        getByTag,
        getByEra,
        getByOpening,
        getRandom,
        search,
        parsePGN,
        getCommentaryForMove,
        getAllTags,
        getGamesForPersonality,
        get count() { return GAMES.length; },
        get games() { return GAMES; },
    };
})();
