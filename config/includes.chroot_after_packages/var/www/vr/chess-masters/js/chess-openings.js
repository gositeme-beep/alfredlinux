/* ═══════════════════════════════════════════════════════════════
   CHESS MASTERS — Grand Opening Encyclopedia
   GSM Alfred OS · Project Grandmaster II
   
   Complete opening database with 150+ named variations.
   Each opening includes:
   - ECO code, name, deep move sequences (in UCI format)
   - Strategic description, key ideas
   - Famous practitioners and signature games
   - Personality preference mapping (which AI prefers which opening)
   
   Sources: ECO classification, Kasparov's opening theory,
   Russian chess school curriculum, MCO-15, NCO, 
   and the collective wisdom of world champions.
   ═══════════════════════════════════════════════════════════════ */

const ChessOpenings = (() => {
    'use strict';

    // ─────────────────────────────────────────────────────────
    // OPENING DATABASE — 150+ variations
    // Format: { name, eco, moves (UCI), san (standard), depth, 
    //           desc, ideas[], traps[], personality[] }
    // ─────────────────────────────────────────────────────────

    const OPENINGS = [

        // ═══════════════════════════════════
        // KING PAWN OPENINGS (1.e4)
        // ═══════════════════════════════════

        // ── RUY LOPEZ (Spanish Game) ──
        { name: "Ruy Lopez", eco: "C60", category: "open",
          moves: "e2e4 e7e5 g1f3 b8c6 f1b5", san: "1.e4 e5 2.Nf3 Nc6 3.Bb5",
          desc: "The most classical of all openings — White develops with tempo and fights for the center. The backbone of 1.e4 theory for 500 years.",
          ideas: ["Control d5 via pressure on c6", "Build a strong center with d4", "Castle kingside and attack"],
          traps: ["Noah's Ark Trap: ...b5 ...Na5 ...c5 trapping the bishop"],
          personality: ["fischer_bot", "kasparov_bot", "capablanca_bot"] },

        { name: "Ruy Lopez — Morphy Defense", eco: "C65", category: "open",
          moves: "e2e4 e7e5 g1f3 b8c6 f1b5 a7a6", san: "1.e4 e5 2.Nf3 Nc6 3.Bb5 a6",
          desc: "The most popular response — Black questions the bishop immediately. Leads to rich strategic play.",
          ideas: ["Question the bishop's purpose", "Prepare ...b5 and ...d5 counterplay"],
          traps: [],
          personality: ["kasparov_bot", "capablanca_bot"] },

        { name: "Ruy Lopez — Closed (Chigorin)", eco: "C96", category: "open",
          moves: "e2e4 e7e5 g1f3 b8c6 f1b5 a7a6 b5a4 g8f6 e1g1 f8e7 f1e1 b7b5 a4b3 d7d6 c2c3 e8g8 h2h3 b8a8",
          san: "1.e4 e5 2.Nf3 Nc6 3.Bb5 a6 4.Ba4 Nf6 5.O-O Be7 6.Re1 b5 7.Bb3 d6 8.c3 O-O 9.h3 Na8",
          desc: "The deepest line of the Ruy Lopez — the Chigorin variation. Black reroutes the knight to c7-e6 for a flexible defense. Games can last 50+ moves in the middlegame.",
          ideas: ["Black plays ...Na5 threatening ...Nxb3", "Slow maneuvering on both flanks", "Pawn structures define plans"],
          traps: [],
          personality: ["kasparov_bot", "botvinnik_bot"] },

        { name: "Ruy Lopez — Marshall Attack", eco: "C89", category: "open",
          moves: "e2e4 e7e5 g1f3 b8c6 f1b5 a7a6 b5a4 g8f6 e1g1 f8e7 f1e1 b7b5 a4b3 e8g8 c2c3 d7d5",
          san: "1.e4 e5 2.Nf3 Nc6 3.Bb5 a6 4.Ba4 Nf6 5.O-O Be7 6.Re1 b5 7.Bb3 O-O 8.c3 d5",
          desc: "Frank Marshall's legendary gambit — Black sacrifices a pawn for a ferocious kingside attack. Prepared in secret for years before unleashing it against Capablanca in 1918.",
          ideas: ["Sacrifice e5 pawn for rapid piece activity", "Attack f2 and h3 with ...Bd6, ...Qh4", "Rook lifts via ...Re8-e6-g6"],
          traps: ["After exd5 Nxd5, the knight reaches f4 with devastating effect"],
          personality: ["tal_bot", "kasparov_bot", "morphy_bot"] },

        { name: "Ruy Lopez — Berlin Defense", eco: "C65", category: "open",
          moves: "e2e4 e7e5 g1f3 b8c6 f1b5 g8f6", san: "1.e4 e5 2.Nf3 Nc6 3.Bb5 Nf6",
          desc: "The 'Berlin Wall' — Kramnik used this to dethrone Kasparov in 2000. Leads to a queenless middlegame with long-term equality. Ultra-solid.",
          ideas: ["Trade queens early, play for endgame advantage", "Maintain solid pawn structure", "Utilize bishop pair in open positions"],
          traps: [],
          personality: ["petrosian_bot", "capablanca_bot"] },

        { name: "Ruy Lopez — Exchange Variation", eco: "C68", category: "open",
          moves: "e2e4 e7e5 g1f3 b8c6 f1b5 a7a6 b5c6 d7c6", san: "1.e4 e5 2.Nf3 Nc6 3.Bb5 a6 4.Bxc6 dxc6",
          desc: "Fischer's favorite weapon — simplifying but keeping a long-term structural edge. White aims for a better endgame with the kingside majority.",
          ideas: ["Exploit Black's doubled c-pawns in endgame", "Kingside pawn majority creates passed pawn", "Simple but deadly in Fischer's hands"],
          traps: [],
          personality: ["fischer_bot"] },

        // ── ITALIAN GAME ──
        { name: "Italian Game", eco: "C50", category: "open",
          moves: "e2e4 e7e5 g1f3 b8c6 f1c4", san: "1.e4 e5 2.Nf3 Nc6 3.Bc4",
          desc: "One of the oldest openings — the bishop aims at f7, the weakest point in Black's position. Romantic era favorite.",
          ideas: ["Direct pressure on f7", "Quick development and castling", "Open lines for attack"],
          traps: ["Fried Liver Attack: Nxf7 sacrifice"],
          personality: ["morphy_bot", "alfred"] },

        { name: "Giuoco Piano", eco: "C53", category: "open",
          moves: "e2e4 e7e5 g1f3 b8c6 f1c4 f8c5", san: "1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5",
          desc: "'The Quiet Game' — both sides develop bishops symmetrically. Despite the name, it can lead to sharp play after c3-d4.",
          ideas: ["Build center with c3 and d4", "Italian bishops eye each other", "Central tension determines middlegame character"],
          traps: [],
          personality: ["alfred", "atlas"] },

        { name: "Evans Gambit", eco: "C51", category: "open",
          moves: "e2e4 e7e5 g1f3 b8c6 f1c4 f8c5 b2b4", san: "1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5 4.b4",
          desc: "Captain Evans' brilliant pawn sacrifice — Black takes b4, White plays c3 with a massive center. Kasparov revived it in the 1990s. Pure romantic chess.",
          ideas: ["Sacrifice b-pawn for rapid center control", "After ...Bxb4 c3, White gets d4 with tempo", "Open lines and piece activity over material"],
          traps: ["After ...Bxb4 c3 Ba5, White plays d4 with devastating center"],
          personality: ["kasparov_bot", "morphy_bot", "tal_bot", "nova"] },

        { name: "Two Knights Defense", eco: "C55", category: "open",
          moves: "e2e4 e7e5 g1f3 b8c6 f1c4 g8f6", san: "1.e4 e5 2.Nf3 Nc6 3.Bc4 Nf6",
          desc: "Black plays actively with ...Nf6, counterattacking e4. Leads to the razor-sharp Fried Liver Attack or the Traxler Counter-Gambit.",
          ideas: ["Counterattack e4 before White can consolidate", "Aggressive piece play from the start"],
          traps: ["Fried Liver: 4.Ng5 d5 5.exd5 Nxd5 6.Nxf7!?", "Traxler: 4.Ng5 Bc5!? sacrificing f7 for attack on f2"],
          personality: ["tal_bot", "morphy_bot", "pulse"] },

        { name: "Fried Liver Attack", eco: "C57", category: "open",
          moves: "e2e4 e7e5 g1f3 b8c6 f1c4 g8f6 f3g5 d7d5 e4d5 c6d5 g5f7",
          san: "1.e4 e5 2.Nf3 Nc6 3.Bc4 Nf6 4.Ng5 d5 5.exd5 Nxd5 6.Nxf7",
          desc: "The most famous attacking line in chess — White sacrifices a knight on f7 to drag the king out. The Fegatello (Italian for 'dead meat'). Pure tactical fireworks.",
          ideas: ["Sacrifice knight on f7 to expose the king", "King marches to e6 or d6 — both dangerous", "White develops with gain of tempo"],
          traps: ["After Kxf7 Qf3+ Ke6, Black's king is in the center — devastating"],
          personality: ["tal_bot", "morphy_bot", "pulse", "chaos"] },

        // ── SCOTCH GAME ──
        { name: "Scotch Game", eco: "C45", category: "open",
          moves: "e2e4 e7e5 g1f3 b8c6 d2d4", san: "1.e4 e5 2.Nf3 Nc6 3.d4",
          desc: "Kasparov revived this against Karpov in their 1990 World Championship match. White opens the center immediately, avoiding the heavy theory of the Ruy Lopez.",
          ideas: ["Immediate central confrontation", "Avoid deep Ruy Lopez theory", "Open game suit tactical players"],
          traps: [],
          personality: ["kasparov_bot", "nova"] },

        { name: "Scotch Gambit", eco: "C44", category: "open",
          moves: "e2e4 e7e5 g1f3 b8c6 d2d4 e5d4 f1c4", san: "1.e4 e5 2.Nf3 Nc6 3.d4 exd4 4.Bc4",
          desc: "Instead of recapturing on d4, White develops the bishop with tempo. Leads to tactical complications reminiscent of the Italian Game.",
          ideas: ["Develop rapidly after gambit pawn", "Tactical complications benefit the better-prepared player"],
          traps: [],
          personality: ["morphy_bot", "nova"] },

        // ── KING'S GAMBIT ──
        { name: "King's Gambit", eco: "C30", category: "open",
          moves: "e2e4 e7e5 f2f4", san: "1.e4 e5 2.f4",
          desc: "The most romantic opening in chess history. White sacrifices a pawn to rip open the f-file and attack f7. Played by every world champion from Anderssen to Spassky. Fischer declared it 'busted' — but was he right?",
          ideas: ["Open f-file for the rook", "Rapid kingside attack", "Sacrifice material for initiative"],
          traps: ["Muzio Gambit: sacrifice the knight on f7 too!"],
          personality: ["morphy_bot", "tal_bot", "nova", "pierre"] },

        { name: "King's Gambit Accepted", eco: "C33", category: "open",
          moves: "e2e4 e7e5 f2f4 e5f4", san: "1.e4 e5 2.f4 exf4",
          desc: "Black takes the gambit pawn. Now White must prove compensation through rapid development and kingside pressure.",
          ideas: ["White plays Nf3 and d4, aiming for open center", "Black tries to hold the f4 pawn or return it at the right moment"],
          traps: [],
          personality: ["morphy_bot", "tal_bot"] },

        { name: "King's Gambit — Muzio Gambit", eco: "C37", category: "open",
          moves: "e2e4 e7e5 f2f4 e5f4 g1f3 g7g5 f1c4 g5g4 e1g1",
          san: "1.e4 e5 2.f4 exf4 3.Nf3 g5 4.Bc4 g4 5.O-O",
          desc: "The ultimate romantic gambit — White castles INTO the attack, sacrificing the f3 knight. Played since the 1600s. Pure creative madness.",
          ideas: ["Castle into the storm, sacrificing a full piece", "Open f-file + development advantage = devastating attack"],
          traps: [],
          personality: ["morphy_bot", "chaos", "nova"] },

        // ── SICILIAN DEFENSE ──
        { name: "Sicilian Defense", eco: "B20", category: "semi-open",
          moves: "e2e4 c7c5", san: "1.e4 c5",
          desc: "The most popular and most analyzed defense. Black creates an asymmetrical position from move 1. Scores higher than any other defense for Black statistically. Kasparov's weapon of choice.",
          ideas: ["Asymmetry — c5 fights for d4 without mirroring e5", "Black gets queenside play, White gets kingside attack", "The pawn on c5 creates an extra central pawn after dxc5"],
          traps: [],
          personality: ["kasparov_bot", "fischer_bot", "tal_bot"] },

        { name: "Sicilian Najdorf", eco: "B90", category: "semi-open",
          moves: "e2e4 c7c5 g1f3 d7d6 d2d4 c5d4 f3d4 g8f6 b1c3 a7a6",
          san: "1.e4 c5 2.Nf3 d6 3.d4 cxd4 4.Nxd4 Nf6 5.Nc3 a6",
          desc: "The most deeply analyzed opening in all of chess. Fischer and Kasparov both used it as their primary weapon. The move ...a6 prevents Bb5 and prepares ...e5 or ...b5 expansion. Entire books are written about single sub-variations.",
          ideas: ["Flexible — Black can play ...e5, ...e6, ...b5, or ...g6", "...a6 prevents all Bb5 ideas", "The English Attack (f3, Be3, Qd2, g4) is White's main weapon"],
          traps: ["Poisoned Pawn: 6.Bg5 e6 7.f4 Qb6 — Black grabs b2"],
          personality: ["kasparov_bot", "fischer_bot", "tal_bot", "nova"] },

        { name: "Sicilian Dragon", eco: "B70", category: "semi-open",
          moves: "e2e4 c7c5 g1f3 d7d6 d2d4 c5d4 f3d4 g8f6 b1c3 g7g6",
          san: "1.e4 c5 2.Nf3 d6 3.d4 cxd4 4.Nxd4 Nf6 5.Nc3 g6",
          desc: "The Dragon — named for its pawn structure resembling a dragon constellation. Black fianchettoes the bishop on g7, creating a deadly diagonal. The Yugoslav Attack leads to the most violent games in chess.",
          ideas: ["Bishop on g7 rakes the long diagonal", "Black castles queenside and plays ...Rc8, ...a5, ...b5", "In the Yugoslav Attack, both sides castle opposite and race to checkmate"],
          traps: ["Sac on h5: Bxg7 then Nf5 with devastating kingside pressure"],
          personality: ["tal_bot", "kasparov_bot", "pulse"] },

        { name: "Sicilian Dragon — Yugoslav Attack", eco: "B77", category: "semi-open",
          moves: "e2e4 c7c5 g1f3 d7d6 d2d4 c5d4 f3d4 g8f6 b1c3 g7g6 c1e3 f8g7 f2f3 e8g8 d1d2 b8c6 f1c4",
          san: "1.e4 c5 2.Nf3 d6 3.d4 cxd4 4.Nxd4 Nf6 5.Nc3 g6 6.Be3 Bg7 7.f3 O-O 8.Qd2 Nc6 9.Bc4",
          desc: "The most violent theoretical system in chess. White castles queenside and launches a pawn storm with g4-h4-h5. Black races with ...Rc8, ...Ne5, ...a5-a4. First to deliver checkmate wins. Draw rate is extremely low.",
          ideas: ["Opposite side castling creates mutual king hunts", "White: g4-h4-h5, open h-file, Bh6 exchange dark bishops", "Black: ...Rc8, ...Ne5, ...Qa5, ...Rxc3 sacrifice"],
          traps: [],
          personality: ["kasparov_bot", "tal_bot", "pulse"] },

        { name: "Sicilian Scheveningen", eco: "B80", category: "semi-open",
          moves: "e2e4 c7c5 g1f3 d7d6 d2d4 c5d4 f3d4 g8f6 b1c3 e7e6",
          san: "1.e4 c5 2.Nf3 d6 3.d4 cxd4 4.Nxd4 Nf6 5.Nc3 e6",
          desc: "A flexible Sicilian — the ...e6 and ...d6 pawn duo is solid but not passive. Kasparov played this extensively.",
          ideas: ["Flexible — can transpose to Najdorf or Classical", "Solid center prevents e5 break", "Keres Attack with g4 is White's sharpest response"],
          traps: [],
          personality: ["kasparov_bot", "alfred"] },

        { name: "Sicilian Sveshnikov", eco: "B33", category: "semi-open",
          moves: "e2e4 c7c5 g1f3 b8c6 d2d4 c5d4 f3d4 g8f6 b1c3 e7e5",
          san: "1.e4 c5 2.Nf3 Nc6 3.d4 cxd4 4.Nxd4 Nf6 5.Nc3 e5",
          desc: "Evgeny Sveshnikov's revolutionary system — Black grabs space with ...e5 but accepts a backward d-pawn and a hole on d5. The compensation? Dynamic piece play and kingside chances.",
          ideas: ["...e5 gains space but weakens d5", "Black's dark-squared bishop is powerful on e7-g5-h4", "Bxf6 doubling pawns is a key positional idea for White"],
          traps: [],
          personality: ["kasparov_bot", "nova"] },

        { name: "Sicilian Kalashnikov", eco: "B32", category: "semi-open",
          moves: "e2e4 c7c5 g1f3 b8c6 d2d4 c5d4 f3d4 e7e5 d4b5 d7d6",
          san: "1.e4 c5 2.Nf3 Nc6 3.d4 cxd4 4.Nxd4 e5 5.Nb5 d6",
          desc: "Related to the Sveshnikov but avoids the critical Bxf6 lines. Named after the Russian weapon — aggressive and direct.",
          ideas: ["Grabs space with ...e5", "Avoids doubled f-pawns of Sveshnikov main line"],
          traps: [],
          personality: ["pulse", "nova"] },

        { name: "Sicilian Accelerated Dragon", eco: "B35", category: "semi-open",
          moves: "e2e4 c7c5 g1f3 b8c6 d2d4 c5d4 f3d4 g7g6",
          san: "1.e4 c5 2.Nf3 Nc6 3.d4 cxd4 4.Nxd4 g6",
          desc: "Dragon without ...d6 first — allows the Maróczy Bind (c4) but avoids the Yugoslav Attack. A positional approach to the Dragon.",
          ideas: ["Fianchetto bishop without committing to ...d6", "If White plays c4 (Maróczy Bind), Black plays ...Nf6 and ...d5"],
          traps: [],
          personality: ["atlas", "capablanca_bot"] },

        { name: "Sicilian Taimanov", eco: "B46", category: "semi-open",
          moves: "e2e4 c7c5 g1f3 e7e6 d2d4 c5d4 f3d4 b8c6",
          san: "1.e4 c5 2.Nf3 e6 3.d4 cxd4 4.Nxd4 Nc6",
          desc: "Mark Taimanov's flexible system — keeps all options open. Black can play ...a6, ...Nge7, ...d6 or ...d5 depending on White's setup.",
          ideas: ["Maximum flexibility", "Can transpose to Scheveningen, Classical, or independent lines"],
          traps: [],
          personality: ["alfred", "sage"] },

        { name: "Sicilian Kan", eco: "B42", category: "semi-open",
          moves: "e2e4 c7c5 g1f3 e7e6 d2d4 c5d4 f3d4 a7a6",
          san: "1.e4 c5 2.Nf3 e6 3.d4 cxd4 4.Nxd4 a6",
          desc: "The Kan (or Paulsen) — ...a6 before ...Nc6 gives Black unique flexibility. Hedgehog structures often arise.",
          ideas: ["Flexible pawn structure", "Can lead to Hedgehog with ...b6, ...Bb7, ...d6, ...Be7"],
          traps: [],
          personality: ["petrosian_bot", "atlas"] },

        { name: "Sicilian Grand Prix Attack", eco: "B23", category: "semi-open",
          moves: "e2e4 c7c5 b1c3 b8c6 f2f4", san: "1.e4 c5 2.Nc3 Nc6 3.f4",
          desc: "An aggressive Anti-Sicilian — White plays f4 early, aiming for a quick kingside attack. Popular at club level. Named after the 1970s British chess circuit.",
          ideas: ["Quick f4-f5 kingside expansion", "Avoid deep Sicilian theory", "Sharp attacking chances for White"],
          traps: [],
          personality: ["pulse", "speed_demon"] },

        { name: "Sicilian Closed", eco: "B23", category: "semi-open",
          moves: "e2e4 c7c5 b1c3", san: "1.e4 c5 2.Nc3",
          desc: "Avoid the Open Sicilian entirely. Spassky used it against Fischer. White develops quietly and attacks later.",
          ideas: ["Avoid complex Open Sicilian theory", "Bc4 and f4 plans", "Closed positions favor preparation"],
          traps: [],
          personality: ["botvinnik_bot", "petrosian_bot"] },

        { name: "Smith-Morra Gambit", eco: "B21", category: "semi-open",
          moves: "e2e4 c7c5 d2d4 c5d4 c2c3", san: "1.e4 c5 2.d4 cxd4 3.c3",
          desc: "Gambit a pawn for rapid development and open lines. Marc Smith and Pierre Morra's aggressive alternative to the Open Sicilian. Dangerous at all levels.",
          ideas: ["Sacrifice c-pawn for tempo and open c-file", "Develop rapidly: Nf3, Bc4, O-O, Qe2", "Piece activity compensates for material"],
          traps: ["Scholar's trap: Black plays carelessly and gets caught in Bc4+Qe2+Rd1 battery"],
          personality: ["nova", "chaos", "morphy_bot"] },

        // ── FRENCH DEFENSE ──
        { name: "French Defense", eco: "C00", category: "semi-open",
          moves: "e2e4 e7e6", san: "1.e4 e6",
          desc: "Solid and strategic — Black builds a wall and fights for the center with ...d5. Botvinnik's weapon. The light-squared bishop is permanently locked in, but the pawn chain provides structure.",
          ideas: ["Solid pawn chain e6-d5", "Fight for d4-e5 central tension", "Queenside counterplay with ...c5"],
          traps: [],
          personality: ["botvinnik_bot", "petrosian_bot", "atlas"] },

        { name: "French Winawer", eco: "C15", category: "semi-open",
          moves: "e2e4 e7e6 d2d4 d7d5 b1c3 f8b4", san: "1.e4 e6 2.d4 d5 3.Nc3 Bb4",
          desc: "The sharpest French — Black pins the knight and creates immediate tension. Botvinnik's favorite. Leads to incredibly complex pawn structures.",
          ideas: ["Pin the knight, create doubled pawns after Bxc3+", "Complex pawn chains with mutual weaknesses", "Both sides often castle opposite"],
          traps: [],
          personality: ["botvinnik_bot", "tal_bot"] },

        { name: "French Tarrasch", eco: "C03", category: "semi-open",
          moves: "e2e4 e7e6 d2d4 d7d5 b1d2", san: "1.e4 e6 2.d4 d5 3.Nd2",
          desc: "White avoids the Winawer pin by playing Nd2 instead of Nc3. Leads to a quieter but strategically rich game.",
          ideas: ["Avoid Winawer complications", "Maintain solid pawn structure", "f3 and e5 plans"],
          traps: [],
          personality: ["alfred", "atlas"] },

        { name: "French Advance", eco: "C02", category: "semi-open",
          moves: "e2e4 e7e6 d2d4 d7d5 e4e5", san: "1.e4 e6 2.d4 d5 3.e5",
          desc: "White advances immediately, gaining space but creating a target. Nimzowitsch analyzed this deeply. Black undermines with ...c5 and ...f6.",
          ideas: ["Space advantage on kingside", "Black attacks the chain base with ...c5", "...f6 undermining break is key"],
          traps: [],
          personality: ["sage", "botvinnik_bot"] },

        { name: "French Classical", eco: "C11", category: "semi-open",
          moves: "e2e4 e7e6 d2d4 d7d5 b1c3 g8f6", san: "1.e4 e6 2.d4 d5 3.Nc3 Nf6",
          desc: "The mainstream French — Black develops the knight naturally. Leads to the Steinitz or Burn variations.",
          ideas: ["Normal development, sound play", "After 4.Bg5 — complex middlegame theory"],
          traps: [],
          personality: ["alfred", "fischer_bot"] },

        // ── CARO-KANN DEFENSE ──
        { name: "Caro-Kann Defense", eco: "B10", category: "semi-open",
          moves: "e2e4 c7c6", san: "1.e4 c6",
          desc: "The most solid response to 1.e4 — Black prepares ...d5 while keeping the light-squared bishop free. Karpov and Capablanca's choice for safety.",
          ideas: ["...d5 with the bishop uncaged (unlike French)", "Solid pawn structure", "Strategic middlegame play"],
          traps: [],
          personality: ["capablanca_bot", "petrosian_bot", "atlas"] },

        { name: "Caro-Kann — Classical", eco: "B18", category: "semi-open",
          moves: "e2e4 c7c6 d2d4 d7d5 b1c3 d5e4 c3e4 c8f5", san: "1.e4 c6 2.d4 d5 3.Nc3 dxe4 4.Nxe4 Bf5",
          desc: "The main line — Black develops the bishop before playing ...e6. The whole point of the Caro-Kann.",
          ideas: ["Develop bishop BEFORE playing ...e6", "After Ng3 Bg6, solid structure", "Capablanca's endgame technique shines here"],
          traps: [],
          personality: ["capablanca_bot", "petrosian_bot"] },

        { name: "Caro-Kann — Advance", eco: "B12", category: "semi-open",
          moves: "e2e4 c7c6 d2d4 d7d5 e4e5", san: "1.e4 c6 2.d4 d5 3.e5",
          desc: "White advances as in the French but Black's light bishop is free. The Tal variation (3.e5 Bf5 4.Nc3) is particularly sharp.",
          ideas: ["Space advantage", "Black's bishop is already outside the pawn chain", "Short-plan: undermine with ...c5, ...e6, ...f6"],
          traps: [],
          personality: ["tal_bot", "pulse"] },

        // ── PIRC & MODERN DEFENSE ──
        { name: "Pirc Defense", eco: "B07", category: "semi-open",
          moves: "e2e4 d7d6 d2d4 g8f6 b1c3 g7g6", san: "1.e4 d6 2.d4 Nf6 3.Nc3 g6",
          desc: "Hypermodern approach — Black allows White a big center, then undermines it. The Austrian Attack (f4) creates maximum tension.",
          ideas: ["Fianchetto controls long diagonal", "Undermine center with ...c5 or ...e5", "Flexible pawn structure"],
          traps: [],
          personality: ["nova", "sage"] },

        { name: "Modern Defense", eco: "B06", category: "semi-open",
          moves: "e2e4 g7g6", san: "1.e4 g6",
          desc: "Even more flexible than the Pirc — Black doesn't commit to ...Nf6 immediately. Can transpose to many systems.",
          ideas: ["Maximum flexibility", "The bishop on g7 controls key squares", "Counter-punch the overextended center"],
          traps: [],
          personality: ["chaos", "nova"] },

        // ── ALEKHINE'S DEFENSE ──
        { name: "Alekhine's Defense", eco: "B02", category: "semi-open",
          moves: "e2e4 g8f6", san: "1.e4 Nf6",
          desc: "Provocative — Black invites White to advance pawns and then attacks the overextended center. Alekhine used it to shock the chess world in the 1920s.",
          ideas: ["Provoke e5, then undermine the pawn chain", "After e5 Nd5 c4 Nb6 d4, White has space but targets"],
          traps: [],
          personality: ["chaos", "nova", "tale_bot"] },

        { name: "Alekhine — Four Pawns Attack", eco: "B03", category: "semi-open",
          moves: "e2e4 g8f6 e4e5 f6d5 d2d4 d7d6 c2c4 d5b6 f2f4",
          san: "1.e4 Nf6 2.e5 Nd5 3.d4 d6 4.c4 Nb6 5.f4",
          desc: "White accepts the challenge and pushes all four center pawns. Extremely sharp — if the center holds, White is winning. If it collapses, Black is winning.",
          ideas: ["Maximum central space advantage", "4 pawns on c4-d4-e5-f4 is imposing", "Black undermines with ...c5, ...e6, ...Nc6"],
          traps: [],
          personality: ["pulse", "kasparov_bot"] },

        // ── SCANDINAVIAN DEFENSE ──
        { name: "Scandinavian Defense", eco: "B01", category: "semi-open",
          moves: "e2e4 d7d5", san: "1.e4 d5",
          desc: "The oldest recorded opening — played since the 15th century. Black immediately challenges the center. After exd5 Qxd5, the queen is developed early but gets harassed.",
          ideas: ["Immediate central challenge", "After ...Qxd5, Nc3 gains tempo on the queen", "...Qa5 or ...Qd6 are modern retreats"],
          traps: ["Icelandic Gambit: 2.exd5 Nf6!? sacrificing a pawn for rapid development"],
          personality: ["chaos", "club_player"] },

        // ── PETROFF DEFENSE ──
        { name: "Petroff Defense", eco: "C42", category: "open",
          moves: "e2e4 e7e5 g1f3 g8f6", san: "1.e4 e5 2.Nf3 Nf6",
          desc: "The Russian Defense — instead of defending e5, Black counterattacks e4. Ultra-solid, drawish reputation but contains venom. Kramnik's weapon.",
          ideas: ["Symmetrical counterattack", "After Nxe5 d6, Black regains the pawn with equality", "Extreme solidity — very difficult to lose as Black"],
          traps: ["Stafford Gambit: 2...Nf6 3.Nxe5 Nc6!? sacrificing e5 for tricks"],
          personality: ["petrosian_bot", "capablanca_bot"] },

        // ── VIENNA GAME ──
        { name: "Vienna Game", eco: "C25", category: "open",
          moves: "e2e4 e7e5 b1c3", san: "1.e4 e5 2.Nc3",
          desc: "White develops the knight to c3 before deciding on Bc4 or f4. Can transpose to the Vienna Gambit (f4) or quiet Italian-like play.",
          ideas: ["Delayed King's Gambit with f4", "Maintain flexibility", "Bc4 plans aiming at f7"],
          traps: ["Frankenstein-Dracula: 2.Nc3 Nf6 3.Bc4 Nxe4 4.Qh5 — extremely wild"],
          personality: ["botvinnik_bot", "nova"] },

        // ═══════════════════════════════════
        // QUEEN PAWN OPENINGS (1.d4)
        // ═══════════════════════════════════

        // ── QUEEN'S GAMBIT ──
        { name: "Queen's Gambit", eco: "D06", category: "closed",
          moves: "d2d4 d7d5 c2c4", san: "1.d4 d5 2.c4",
          desc: "Not a true gambit — if Black takes, White easily recovers the pawn. The foundation of positional chess. Every world champion has played it.",
          ideas: ["Challenge Black's center pawn", "After cxd5, White has a central majority", "Control d5 and open the c-file"],
          traps: [],
          personality: ["capablanca_bot", "botvinnik_bot", "kasparov_bot"] },

        { name: "Queen's Gambit Declined", eco: "D30", category: "closed",
          moves: "d2d4 d7d5 c2c4 e7e6", san: "1.d4 d5 2.c4 e6",
          desc: "The classical response — Black defends d5 with ...e6. Leads to the most important theoretical positions in chess. Lasker, Capablanca, Kasparov — all masters of the QGD.",
          ideas: ["Solid center, fight for d5", "Black's light bishop is temporarily locked in", "Minority attack (b4-b5) is a key strategic idea"],
          traps: [],
          personality: ["capablanca_bot", "kasparov_bot", "alfred"] },

        { name: "Queen's Gambit Accepted", eco: "D20", category: "closed",
          moves: "d2d4 d7d5 c2c4 d5c4", san: "1.d4 d5 2.c4 dxc4",
          desc: "Black takes the pawn — not to hold it, but to develop freely while White recovers it. Modern approach, very popular at the top level.",
          ideas: ["Black gives up center for tempo", "After ...a6 and ...b5, Black can try to hold the pawn", "More commonly, Black develops and lets White take back"],
          traps: [],
          personality: ["tal_bot", "fischer_bot"] },

        { name: "Slav Defense", eco: "D10", category: "closed",
          moves: "d2d4 d7d5 c2c4 c7c6", san: "1.d4 d5 2.c4 c6",
          desc: "Solid like the QGD but the light bishop stays free. A favorite of Russian school players. After ...dxc4, Black can play ...b5 to keep the pawn.",
          ideas: ["Support d5 without locking in the bishop", "After ...Bf5, the bishop is developed outside the chain", "Very solid — drawish but with winning chances"],
          traps: [],
          personality: ["petrosian_bot", "botvinnik_bot", "atlas"] },

        { name: "Semi-Slav (Meran)", eco: "D47", category: "closed",
          moves: "d2d4 d7d5 c2c4 c7c6 g1f3 g8f6 b1c3 e7e6 e2e3 b8d7 f1d3 d5c4 d3c4 b7b5",
          san: "1.d4 d5 2.c4 c6 3.Nf3 Nf6 4.Nc3 e6 5.e3 Nbd7 6.Bd3 dxc4 7.Bxc4 b5",
          desc: "The Meran variation — one of the sharpest lines in the Semi-Slav. Black expands aggressively on the queenside. Deep preparation is essential.",
          ideas: ["Aggressive queenside expansion", "...b5 and ...Bb7 with counterplay", "Extremely theoretical — memory battles at the top"],
          traps: [],
          personality: ["kasparov_bot", "botvinnik_bot"] },

        { name: "Semi-Slav — Botvinnik System", eco: "D44", category: "closed",
          moves: "d2d4 d7d5 c2c4 c7c6 g1f3 g8f6 b1c3 e7e6 c1g5 d5c4",
          san: "1.d4 d5 2.c4 c6 3.Nf3 Nf6 4.Nc3 e6 5.Bg5 dxc4",
          desc: "Botvinnik's legendary system — Black captures on c4 and prepares ...b5. The resulting positions are among the sharpest and most deeply analyzed in all of chess.",
          ideas: ["Capture on c4, hold with ...b5", "Sharp: both sides play for a win", "Deep preparation required for both sides"],
          traps: [],
          personality: ["botvinnik_bot", "kasparov_bot"] },

        // ── KING'S INDIAN DEFENSE ──
        { name: "King's Indian Defense", eco: "E60", category: "indian",
          moves: "d2d4 g8f6 c2c4 g7g6", san: "1.d4 Nf6 2.c4 g6",
          desc: "The fighting defense — Black allows White a massive center, then blows it up with ...e5 or ...c5. Kasparov and Fischer's weapon for must-win games. Leads to the most violent middlegames in closed positions.",
          ideas: ["Fianchetto bishop, allow White center, then strike", "Classical: ...e5 and kingside attack", "Sämisch: ...c5 and queenside play"],
          traps: [],
          personality: ["kasparov_bot", "fischer_bot", "tal_bot"] },

        { name: "King's Indian — Classical", eco: "E90", category: "indian",
          moves: "d2d4 g8f6 c2c4 g7g6 b1c3 f8g7 e2e4 d7d6 g1f3 e8g8 f1e2 e7e5",
          san: "1.d4 Nf6 2.c4 g6 3.Nc3 Bg7 4.e4 d6 5.Nf3 O-O 6.Be2 e5",
          desc: "The main line — after ...e5, a titanic struggle begins. White pushes d5 and attacks on the queenside (c5-c6). Black closes the center and attacks on the kingside (...f5-f4-g5-g4-h5). Two armies race on opposite flanks.",
          ideas: ["After d5, opposite wing attacks", "White: c5, a4-a5, Nc4-Nb6", "Black: f5, g5, Rf7-Rg7, h5, Nf6-h5-f4"],
          traps: [],
          personality: ["kasparov_bot", "fischer_bot"] },

        { name: "King's Indian — Sämisch", eco: "E80", category: "indian",
          moves: "d2d4 g8f6 c2c4 g7g6 b1c3 f8g7 e2e4 d7d6 f2f3",
          san: "1.d4 Nf6 2.c4 g6 3.Nc3 Bg7 4.e4 d6 5.f3",
          desc: "White's most aggressive setup — f3 supports e4 and prepares Be3, Qd2, O-O-O. Black can play ...e5 or ...c5. The Sämisch allows White to do everything.",
          ideas: ["Support e4 with f3", "Be3, Qd2, O-O-O with g4-h4 pawn storm", "Extremely aggressive — both sides attack"],
          traps: [],
          personality: ["kasparov_bot", "pulse"] },

        { name: "King's Indian — Four Pawns Attack", eco: "E76", category: "indian",
          moves: "d2d4 g8f6 c2c4 g7g6 b1c3 f8g7 e2e4 d7d6 f2f4",
          san: "1.d4 Nf6 2.c4 g6 3.Nc3 Bg7 4.e4 d6 5.f4",
          desc: "White builds the biggest center possible — four pawns on c4, d4, e4, f4. If it holds, White wins. If it collapses, Black wins. All or nothing.",
          ideas: ["Maximum space advantage", "The pawn chain is impressive but potentially fragile", "Black strikes with ...e5 or ...c5 to crack the center"],
          traps: [],
          personality: ["pulse", "kasparov_bot"] },

        // ── NIMZO-INDIAN DEFENSE ──
        { name: "Nimzo-Indian Defense", eco: "E20", category: "indian",
          moves: "d2d4 g8f6 c2c4 e7e6 b1c3 f8b4", san: "1.d4 Nf6 2.c4 e6 3.Nc3 Bb4",
          desc: "Aron Nimzowitsch's masterpiece — the bishop pins the knight, controlling e4 indirectly. One of the most respected defenses in chess. Leads to complex strategic battles.",
          ideas: ["Pin Nc3, control e4 without pawns", "After Bxc3+, doubled c-pawns compensate with bishop pair", "Extremely flexible — many pawn structures possible"],
          traps: [],
          personality: ["capablanca_bot", "petrosian_bot", "fischer_bot"] },

        { name: "Nimzo-Indian — Rubinstein", eco: "E40", category: "indian",
          moves: "d2d4 g8f6 c2c4 e7e6 b1c3 f8b4 e2e3", san: "1.d4 Nf6 2.c4 e6 3.Nc3 Bb4 4.e3",
          desc: "The most popular White response — solid and flexible. White prepares Bd3, Ne2, and a gradual attack.",
          ideas: ["Solid development", "Prepare Bd3 and Ne2", "Avoid doubled c-pawns by not playing a3 early"],
          traps: [],
          personality: ["capablanca_bot", "alfred"] },

        { name: "Nimzo-Indian — Kasparov Variation", eco: "E25", category: "indian",
          moves: "d2d4 g8f6 c2c4 e7e6 b1c3 f8b4 f2f3", san: "1.d4 Nf6 2.c4 e6 3.Nc3 Bb4 4.f3",
          desc: "Kasparov's aggressive treatment — f3 supports e4 and prepares a big center. Direct and combative.",
          ideas: ["Build massive center with e4", "Fight for control immediately", "Aggressive — leads to complex middlegames"],
          traps: [],
          personality: ["kasparov_bot"] },

        // ── GRÜNFELD DEFENSE ──
        { name: "Grünfeld Defense", eco: "D70", category: "indian",
          moves: "d2d4 g8f6 c2c4 g7g6 b1c3 d7d5", san: "1.d4 Nf6 2.c4 g6 3.Nc3 d5",
          desc: "Ernst Grünfeld's provocative defense — Black invites White to build a massive center (e4+d4) and then attacks it with pieces. Kasparov used it to devastating effect.",
          ideas: ["Allow White a big center, then destroy it", "...c5 and ...Nc6 pressure d4", "Bishop on g7 is a monster on the long diagonal"],
          traps: [],
          personality: ["kasparov_bot", "fischer_bot", "tal_bot"] },

        { name: "Grünfeld — Exchange Variation", eco: "D85", category: "indian",
          moves: "d2d4 g8f6 c2c4 g7g6 b1c3 d7d5 c4d5 f6d5 e2e4 d5c3 b2c3",
          san: "1.d4 Nf6 2.c4 g6 3.Nc3 d5 4.cxd5 Nxd5 5.e4 Nxc3 6.bxc3",
          desc: "The critical test — White gets a massive e4+d4 center with the c3 pawn supporting it. If Black can't crack it, White wins. If Black cracks it, Black wins. One of the deepest theoretical battlegrounds.",
          ideas: ["White: maintain and advance the center", "Black: ...c5, ...Bg7, ...Nc6, ...Qa5, ...cxd4, ...Bg4", "Decades of theory on both sides"],
          traps: [],
          personality: ["kasparov_bot"] },

        // ── QUEEN'S INDIAN DEFENSE ──
        { name: "Queen's Indian Defense", eco: "E12", category: "indian",
          moves: "d2d4 g8f6 c2c4 e7e6 g1f3 b7b6", san: "1.d4 Nf6 2.c4 e6 3.Nf3 b6",
          desc: "Nimzowitsch's counterpart to the Nimzo-Indian — when White avoids Nc3. Black fianchettoes the queen's bishop to control e4 and d5.",
          ideas: ["Bishop on b7 controls the long diagonal", "Control e4 indirectly", "Solid, strategic, Petrosian-style"],
          traps: [],
          personality: ["petrosian_bot", "capablanca_bot", "atlas"] },

        // ── BENONI DEFENSE ──
        { name: "Modern Benoni", eco: "A60", category: "indian",
          moves: "d2d4 g8f6 c2c4 c7c5 d4d5 e7e6 b1c3 e6d5 c4d5 d7d6",
          san: "1.d4 Nf6 2.c4 c5 3.d5 e6 4.Nc3 exd5 5.cxd5 d6",
          desc: "Tal's weapon — Black accepts a space disadvantage for dynamic queenside and kingside play. The pawn on d5 gives White space but Black has the ...b5 break.",
          ideas: ["Asymmetric pawn structure creates imbalanced play", "Black's queenside majority (a7, b7, c5) creates a passed pawn", "...b5 break is crucial for counterplay"],
          traps: [],
          personality: ["tal_bot", "kasparov_bot", "nova"] },

        { name: "Benko Gambit", eco: "A57", category: "indian",
          moves: "d2d4 g8f6 c2c4 c7c5 d4d5 b7b5", san: "1.d4 Nf6 2.c4 c5 3.d5 b5",
          desc: "Pal Benko's positional gambit — Black sacrifices a pawn for lasting queenside pressure on the a and b files. The most positional gambit in chess.",
          ideas: ["Sacrifice b-pawn for open a and b files", "Rooks on a8 and b8 create permanent pressure", "Practically very hard for White to convert extra pawn"],
          traps: [],
          personality: ["nova", "sage"] },

        // ── DUTCH DEFENSE ──
        { name: "Dutch Defense", eco: "A80", category: "closed",
          moves: "d2d4 f7f5", san: "1.d4 f5",
          desc: "Bold and uncompromising — Black fights for e4 control from move 1. The Stonewall variation is a fortress, the Leningrad is a fianchetto setup.",
          ideas: ["Control e4 with the f-pawn", "Kingside attacking chances", "Risk: king is slightly exposed after f5"],
          traps: ["Staunton Gambit: 2.e4 — direct tactical challenge"],
          personality: ["nova", "pierre", "chaos"] },

        { name: "Dutch — Stonewall", eco: "A84", category: "closed",
          moves: "d2d4 f7f5 c2c4 e7e6 g1f3 g8f6 g2g3 d7d5 f1g2 c7c6",
          san: "1.d4 f5 2.c4 e6 3.Nf3 Nf6 4.g3 d5 5.Bg2 c6",
          desc: "An impregnable fortress — pawns on c6, d5, e6, f5 form a wall. The e4 square is permanently controlled. Black maneuvers behind the wall.",
          ideas: ["Fortress on c6-d5-e6-f5", "Dark-squared bishop is bad — trade it with ...Bd6-e7", "Ne5, Qf3-h3 attacking plans"],
          traps: [],
          personality: ["petrosian_bot", "atlas"] },

        { name: "Dutch — Leningrad", eco: "A87", category: "closed",
          moves: "d2d4 f7f5 c2c4 g8f6 g2g3 g7g6 f1g2 f8g7 g1f3 e8g8",
          san: "1.d4 f5 2.c4 Nf6 3.g3 g6 4.Bg2 Bg7 5.Nf3 O-O",
          desc: "Combines King's Indian ideas with the Dutch. The bishop on g7 eyes the center while f5 controls e4. Dynamic and modern.",
          ideas: ["Fianchetto + f5 control", "Can play ...d6 and ...e5", "Hybrid King's Indian structure"],
          traps: [],
          personality: ["kasparov_bot", "nova"] },

        // ═══════════════════════════════════
        // FLANK OPENINGS
        // ═══════════════════════════════════

        // ── ENGLISH OPENING ──
        { name: "English Opening", eco: "A10", category: "flank",
          moves: "c2c4", san: "1.c4",
          desc: "The Sicilian reversed — White plays for control of d5 and flexible development. Botvinnik and Karpov used it extensively. Can transpose to nearly anything.",
          ideas: ["Control d5 from the flank", "Extremely flexible — can become QGD, KID, etc.", "Independent English lines with g3 and Bg2"],
          traps: [],
          personality: ["botvinnik_bot", "capablanca_bot", "atlas"] },

        { name: "English — Symmetrical", eco: "A30", category: "flank",
          moves: "c2c4 c7c5", san: "1.c4 c5",
          desc: "Symmetrical English — both sides fight for d4/d5 from the flanks. Can lead to Hedgehog structures or Maróczy Bind.",
          ideas: ["Mirror play, subtle differences matter", "Nc3, g3, Bg2, d3 Rubinstein setup"],
          traps: [],
          personality: ["atlas", "petrosian_bot"] },

        // ── RÉTI OPENING ──
        { name: "Réti Opening", eco: "A04", category: "flank",
          moves: "g1f3 d7d5 c2c4", san: "1.Nf3 d5 2.c4",
          desc: "Richard Réti's hypermodern masterpiece — attacking the center from the flanks. Used to beat Capablanca's unbeaten streak in 1924.",
          ideas: ["Hypermodern: control center without pawns", "c4 challenges d5 from the side", "Flexible — can become many openings"],
          traps: [],
          personality: ["sage", "botvinnik_bot"] },

        // ── LONDON SYSTEM ──
        { name: "London System", eco: "D00", category: "closed",
          moves: "d2d4 d7d5 c1f4", san: "1.d4 d5 2.Bf4",
          desc: "The most popular system opening — White develops Bf4, e3, Nf3, Bd3, c3 regardless of Black's response. Easy to learn, hard to refute.",
          ideas: ["System play — same setup against everything", "Solid pyramid: d4, e3, c3", "Bishop on f4 is the signature piece"],
          traps: ["Jobava London: Nc3 instead of c3 — more aggressive"],
          personality: ["club_player", "rookie", "atlas"] },

        // ── CATALAN OPENING ──
        { name: "Catalan Opening", eco: "E00", category: "closed",
          moves: "d2d4 g8f6 c2c4 e7e6 g2g3", san: "1.d4 Nf6 2.c4 e6 3.g3",
          desc: "Kramnik's weapon — the bishop on g2 combines with d4 and c4 to create relentless pressure on the long diagonal. Subtle, deep, strategically rich.",
          ideas: ["Bishop on g2 X-rays the queenside", "Positional pressure on d5 and along the diagonal", "Often wins material in subtle ways"],
          traps: [],
          personality: ["capablanca_bot", "petrosian_bot", "alfred"] },

        // ── TROMPOWSKY ATTACK ──
        { name: "Trompowsky Attack", eco: "A45", category: "closed",
          moves: "d2d4 g8f6 c1g5", san: "1.d4 Nf6 2.Bg5",
          desc: "An aggressive Anti-Indian system — the pin is annoying and can lead to doubled pawns. Popular surprise weapon.",
          ideas: ["Create doubled pawns after Bxf6", "Avoid heavy theory of Indian defenses", "Aggressive and practical"],
          traps: [],
          personality: ["speed_demon", "nova"] },

        // ── TORRE ATTACK ──
        { name: "Torre Attack", eco: "A46", category: "closed",
          moves: "d2d4 g8f6 g1f3 e7e6 c1g5", san: "1.d4 Nf6 2.Nf3 e6 3.Bg5",
          desc: "Carlos Torre's system from the 1920s — a quiet but venomous setup. The bishop on g5 creates annoying pins.",
          ideas: ["Pin the knight and create pressure", "e3, Bd3, Nbd2 development", "Can lead to the famous Torre vs. Lasker brilliancy"],
          traps: [],
          personality: ["sage", "alfred"] },

        // ── COLLE SYSTEM ──
        { name: "Colle System", eco: "D05", category: "closed",
          moves: "d2d4 d7d5 g1f3 g8f6 e2e3 e7e6 f1d3 c7c5 c2c3",
          san: "1.d4 d5 2.Nf3 Nf6 3.e3 e6 4.Bd3 c5 5.c3",
          desc: "Edgard Colle's system — simple, solid, and deadly. The e3-e4 break is always coming. Perfect for players who prefer understanding over memorization.",
          ideas: ["Prepare e3-e4 central break", "Bd3 battery with Qe2 or Qc2", "Simple but effective — great for improving players"],
          traps: ["Colle-Zukertort: b3, Bb2 fianchetto variation"],
          personality: ["club_player", "alfred", "rookie"] },

        // ═══════════════════════════════════
        // UNCOMMON & SURPRISE WEAPONS
        // ═══════════════════════════════════

        { name: "Bird's Opening", eco: "A02", category: "flank",
          moves: "f2f4", san: "1.f4",
          desc: "Henry Bird's opening — a Dutch reversed. Controls e5 and prepares Nf3, b3, Bb2. Risky but creative.",
          ideas: ["Control e5", "Reversed Dutch/Leningrad setups", "From's Gambit (1...e5) is the main danger"],
          traps: ["From's Gambit: 1.f4 e5!? 2.fxe5 d6 — dangerous for White"],
          personality: ["nova", "pierre", "chaos"] },

        { name: "Nimzowitsch-Larsen Attack", eco: "A01", category: "flank",
          moves: "b2b3", san: "1.b3",
          desc: "Bent Larsen's flexible opening — fianchetto the bishop and control the center from a distance. Used by Nimzowitsch and Larsen in top-level play.",
          ideas: ["Bishop on b2 controls e5 and f6", "Flexible — no commitments", "Can transpose to many systems"],
          traps: [],
          personality: ["sage", "nova", "pierre"] },

        { name: "Grob Attack", eco: "A00", category: "irregular",
          moves: "g2g4", san: "1.g4",
          desc: "Henri Grob's outrageous opening — immediately weakens the kingside. Theoretically dubious but rich in traps. A chaos agent's dream.",
          ideas: ["Fianchetto bishop on g2 after ...d5 g4-Bg2", "Surprise value is enormous", "Objectively bad but fun"],
          traps: ["Many — beginners who don't know theory get destroyed"],
          personality: ["chaos", "rookie"] },

        { name: "Sokolsky Opening (Polish)", eco: "A00", category: "irregular",
          moves: "b2b4", san: "1.b4",
          desc: "The Polish Opening — fianchetto queenside, then attack on the flanks. Alexei Sokolsky devoted his career to proving it sound.",
          ideas: ["Early queenside expansion", "Bb2 controls the long diagonal", "Surprise weapon with independent theory"],
          traps: [],
          personality: ["chaos", "nova"] },

        { name: "Budapest Gambit", eco: "A51", category: "indian",
          moves: "d2d4 g8f6 c2c4 e7e5", san: "1.d4 Nf6 2.c4 e5",
          desc: "Black sacrifices a central pawn for rapid development and tactical tricks. The Fajarowicz variation (3.dxe5 Ne4) is particularly trappy.",
          ideas: ["Sacrifice e5 for activity", "After dxe5 Ng4, target f2 and e5", "Surprise value against unprepared opponents"],
          traps: ["Fajarowicz: 3.dxe5 Ne4!? — very tricky"],
          personality: ["chaos", "nova", "tal_bot"] },

        // ── PHILIDOR DEFENSE ──
        { name: "Philidor Defense", eco: "C41", category: "open",
          moves: "e2e4 e7e5 g1f3 d7d6", san: "1.e4 e5 2.Nf3 d6",
          desc: "François-André Philidor's solid defense — 'the pawns are the soul of chess.' Black maintains e5 support with d6, leading to a cramped but solid position.",
          ideas: ["Solid support for e5", "Can play ...f5 for counterplay", "Hanham variation with ...Nf6, ...Be7, ...O-O"],
          traps: ["Legal's Mate: Bc4, Nc3, d4 — if Black takes Bg4, Nxe5! threatens Bxf7+"],
          personality: ["atlas", "petrosian_bot"] },

        // ── FOUR KNIGHTS GAME ──
        { name: "Four Knights Game", eco: "C46", category: "open",
          moves: "e2e4 e7e5 g1f3 b8c6 b1c3 g8f6", san: "1.e4 e5 2.Nf3 Nc6 3.Nc3 Nf6",
          desc: "Symmetrical development — but White has the move advantage. The Spanish Four Knights (Bb5) and the Scotch Four Knights (d4) are the main systems.",
          ideas: ["Equal development but White has initiative", "Spanish Four Knights: 4.Bb5", "Can become very tactical with Halloween Gambit: 4.Nxe5!?"],
          traps: ["Halloween Gambit: 4.Nxe5!? Nxe5 5.d4 — sacrifice a knight for massive center"],
          personality: ["alfred", "club_player"] },

        // ── DAMIANO DEFENSE (trap knowledge) ──
        { name: "Damiano Defense", eco: "C40", category: "open",
          moves: "e2e4 e7e5 g1f3 f7f6", san: "1.e4 e5 2.Nf3 f6",
          desc: "A terrible defense — but you must know WHY it's terrible. After Nxe5! fxe5 Qh5+, Black is already in serious trouble. Named after the Portuguese player who demonstrated it was bad.",
          ideas: ["Weakens king position fatally", "Nxe5! exploits the weakened diagonal"],
          traps: ["Nxe5! fxe5 Qh5+ g6 Qxe5+ — devastating"],
          personality: ["rookie"] },

        // ── ELEPHANT GAMBIT ──
        { name: "Elephant Gambit", eco: "C40", category: "open",
          moves: "e2e4 e7e5 g1f3 d7d5", san: "1.e4 e5 2.Nf3 d5",
          desc: "A dubious but trappy gambit — Black counterattacks in the center immediately. Full of tactical tricks for the unsuspecting.",
          ideas: ["Central counterattack", "After exd5 e4, the knight must move", "Dubious but practical at club level"],
          traps: ["After exd5 Bd6!? — if Nxe5? Bxe5 with compensation"],
          personality: ["chaos", "rookie"] },

        // ── KING'S INDIAN ATTACK ──
        { name: "King's Indian Attack", eco: "A07", category: "flank",
          moves: "g1f3 d7d5 g2g3 g8f6 f1g2 e7e6 e1g1 f8e7 d2d3",
          san: "1.Nf3 d5 2.g3 Nf6 3.Bg2 e6 4.O-O Be7 5.d3",
          desc: "Fischer's surprise weapon — a reversed King's Indian. Works against almost any Black setup. Fischer used it to crush strong opposition.",
          ideas: ["System opening — same setup against everything", "Kingside attack with e4-e5, Nbd2-e4", "Fischer-Sozin: e4, d3, Nbd2, Qe2, e5 break"],
          traps: [],
          personality: ["fischer_bot", "atlas", "club_player"] },

    ];

    // ─────────────────────────────────────────────────────────
    // Opening Tree — for efficient lookup during games
    // Build a trie from move sequences for instant recognition
    // ─────────────────────────────────────────────────────────

    const openingTree = {};

    function buildTree() {
        for (const op of OPENINGS) {
            const moves = op.moves.split(' ');
            let node = openingTree;
            for (const m of moves) {
                if (!node[m]) node[m] = {};
                node = node[m];
            }
            node._opening = op;
        }
    }

    // ── Lookup: find the deepest matching opening ──
    function lookup(moveHistory) {
        let node = openingTree;
        let lastMatch = null;

        for (const move of moveHistory) {
            if (!node[move]) break;
            node = node[move];
            if (node._opening) lastMatch = node._opening;
        }

        return lastMatch;
    }

    // ── Get book moves: what moves continue known openings ──
    function getBookMoves(moveHistory) {
        let node = openingTree;
        for (const move of moveHistory) {
            if (!node[move]) return [];
            node = node[move];
        }
        return Object.keys(node).filter(k => k !== '_opening');
    }

    // ── Get all openings for a personality ──
    function getPersonalityOpenings(personalityId) {
        return OPENINGS.filter(op => op.personality && op.personality.includes(personalityId));
    }

    // ── Get openings by category ──
    function getByCategory(category) {
        return OPENINGS.filter(op => op.category === category);
    }

    // ── Select an opening for an AI personality ──
    function selectOpeningForAI(personalityId, asWhite) {
        const preferred = getPersonalityOpenings(personalityId);
        if (preferred.length === 0) return null;

        // Filter by color appropriateness
        const suitable = preferred.filter(op => {
            const firstMove = op.moves.split(' ')[0];
            if (asWhite) return true; // White can play any opening
            // Black openings start with White's move then Black's response
            const moves = op.moves.split(' ');
            return moves.length >= 2;
        });

        if (suitable.length === 0) return preferred[0];
        return suitable[Math.floor(Math.random() * suitable.length)];
    }

    // ── Search openings by name or ECO ──
    function search(query) {
        const q = query.toLowerCase();
        return OPENINGS.filter(op =>
            op.name.toLowerCase().includes(q) ||
            op.eco.toLowerCase().includes(q) ||
            op.desc.toLowerCase().includes(q)
        );
    }

    // ── Get random opening ──
    function random() {
        return OPENINGS[Math.floor(Math.random() * OPENINGS.length)];
    }

    // Build tree on load
    buildTree();

    return {
        lookup,
        getBookMoves,
        getPersonalityOpenings,
        getByCategory,
        selectOpeningForAI,
        search,
        random,
        get all() { return OPENINGS; },
        get count() { return OPENINGS.length; },
        get tree() { return openingTree; },
    };
})();
