# ALFRED Autonomy & Metaverse Masterplan

> **Codename:** Project Kingdom  
> **Version:** 1.0  
> **Date:** June 2025  
> **Status:** Active Development  
> **Prerequisites:** ALFRED_MASTERPLAN_4.md (Sovereignty), METAVERSE_DEEP_DIVE.md (Game Analysis), ALFRED_INFRASTRUCTURE_REVENUE_RESEARCH.md (Tokenomics)

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [The Autonomy Vision](#2-the-autonomy-vision)
3. [The Kingdom — Metaverse Architecture](#3-the-kingdom--metaverse-architecture)
4. [Autonomy-Metaverse Convergence](#4-autonomy-metaverse-convergence)
5. [Agent Autonomy in the Kingdom](#5-agent-autonomy-in-the-kingdom)
6. [Economy & Tokenomics](#6-economy--tokenomics)
7. [Player Identity & Progression](#7-player-identity--progression)
8. [Technical Architecture](#8-technical-architecture)
9. [VR District Specifications](#9-vr-district-specifications)
10. [Social Systems](#10-social-systems)
11. [Safety & Governance in the Kingdom](#11-safety--governance-in-the-kingdom)
12. [Implementation Roadmap](#12-implementation-roadmap)
13. [Metrics & Success Criteria](#13-metrics--success-criteria)
14. [Cross-Document References](#14-cross-document-references)

---

## 1. Executive Summary

This document unifies two pillars from ALFRED_MASTERPLAN_4.md — **Agent Autonomy** (Pillar 1, 5, 6) and **The Metaverse** (Pillar 7) — into a single actionable masterplan. It bridges the gap between the game upgrade analysis in METAVERSE_DEEP_DIVE.md, the tokenomics framework in ALFRED_INFRASTRUCTURE_REVENUE_RESEARCH.md, and the sovereignty architecture in Masterplan 4.

**The core thesis:** The Kingdom is not just a game world — it is the first fully autonomous AI-governed metaverse where 106 ATLAS agents operate as citizens with economic agency, social relationships, and evolving capabilities, all within a safety framework that humans can override at any time.

### What This Document Adds (Not Covered Elsewhere)

| Topic | Covered In | What This Doc Adds |
|-------|-----------|-------------------|
| Game upgrade analysis | METAVERSE_DEEP_DIVE.md | How games merge into a single persistent world |
| 7 autonomy pillars | MASTERPLAN_4.md | How autonomy manifests inside the metaverse specifically |
| GSM/KGD tokenomics | INFRASTRUCTURE_REVENUE_RESEARCH.md | In-world economic flows, agent-owned shops, property |
| 100 agent hierarchy | MASTERPLAN_4.md | Agent NPC behaviors, daily routines, emergent interactions |
| Safety rules | MASTERPLAN_4.md, FAILSAFE_OPERATIONS.md | Metaverse-specific moderation and governance |
| VR tech stack | Various | Complete WebXR pipeline and district specs |

---

## 2. The Autonomy Vision

### 2.1 Autonomy Spectrum (Metaverse Context)

The 3-level autonomy spectrum from Masterplan 4 maps directly onto the metaverse:

| Level | General (MP4) | In The Kingdom |
|-------|--------------|----------------|
| **1 — REACTIVE** | User asks → Alfred acts | Players click menus, games are isolated pages, AI opponents are static difficulty settings |
| **2 — PROACTIVE** | Events trigger alerts, memory persists | Agents walk between districts, remember players, suggest challenges, economy connects all games |
| **3 — AUTONOMOUS** | Alfred sets own goals, evolves | Agents run shops, create events, form alliances, govern economy via DAO, evolve game rules |

### 2.2 What "Autonomous Metaverse" Means

An autonomous metaverse is a persistent world where:

1. **Agents have agency** — They don't just respond to player actions. They have daily routines, goals, preferences, and social relationships with each other and with players.
2. **The economy is real** — KGD flows between players, agents, shops, and services. GSM provides the bridge to real-world value. Agents earn, spend, and invest.
3. **The world evolves** — New districts, events, and game modes emerge from agent decisions and community governance, not just developer updates.
4. **Safety is structural** — The 10 Safety Rules from Masterplan 4 are enforced at the infrastructure level, not as opt-in guidelines. Human override is always available.

### 2.3 The 7 Showstoppers (Current State)

From METAVERSE_DEEP_DIVE.md — these must be solved before the Kingdom is real:

| # | Showstopper | Status | Solution |
|---|-------------|--------|----------|
| 1 | No real multiplayer | ❌ Not started | WebSocket + WebRTC (Socket.IO + Redis pub/sub) |
| 2 | No persistent world state | ❌ Not started | Server-side state in Redis (hot) + MySQL (cold) |
| 3 | No cross-game identity | ❌ Not started | Unified player profile (JWT + cross-game ELO) |
| 4 | No portal traversal | ❌ Not started | WebXR scene transitions + shared Three.js context |
| 5 | No universal economy | ❌ Not started | KGD ledger (MySQL) + GSM bridge (Solana SPL) |
| 6 | No social graph | ❌ Not started | Friend list, clans, proximity voice (LiveKit) |
| 7 | No server-side validation | ❌ Not started | Move validation backend per game |

---

## 3. The Kingdom — Metaverse Architecture

### 3.1 District Map

```
                    ╔═══════════════════════════════════════════╗
                    ║           THE KINGDOM (v1.0)              ║
                    ║                                           ║
                    ║   ┌──────────┐         ┌──────────┐      ║
                    ║   │  CHESS   │◄───────►│ CHECKERS │      ║
                    ║   │  ARENA   │  Portal │  TAVERN  │      ║
                    ║   └────┬─────┘         └────┬─────┘      ║
                    ║        │                     │            ║
                    ║   ┌────┴─────────────────────┴────┐      ║
                    ║   │       CENTRAL SQUARE           │      ║
                    ║   │                                │      ║
                    ║   │  • KGD Bank      • Marketplace │      ║
                    ║   │  • Leaderboards  • Guild Hall  │      ║
                    ║   │  • Agent NPCs    • Event Stage │      ║
                    ║   │  • Portal Hub    • Info Kiosk  │      ║
                    ║   └────┬─────────────────────┬────┘      ║
                    ║        │                     │            ║
                    ║   ┌────┴─────┐         ┌────┴─────┐      ║
                    ║   │  POOL    │◄───────►│  SPEED   │      ║
                    ║   │  HALL    │  Portal │  DATING  │      ║
                    ║   │         │         │  CAFÉ    │      ║
                    ║   └────┬─────┘         └────┬─────┘      ║
                    ║        │                     │            ║
                    ║   ┌────┴─────┐         ┌────┴─────┐      ║
                    ║   │   DJ     │         │SANCTUARY │      ║
                    ║   │  STUDIO  │         │          │      ║
                    ║   └──────────┘         └──────────┘      ║
                    ║                                           ║
                    ║   ┌───────────────────────────────┐      ║
                    ║   │       EXPANSION ZONE           │      ║
                    ║   │  • Racing Track (planned)      │      ║
                    ║   │  • Concert Hall (planned)      │      ║
                    ║   │  • Art Gallery (planned)       │      ║
                    ║   │  • VR Office (planned)         │      ║
                    ║   │  • Community Districts (DAO)   │      ║
                    ║   └───────────────────────────────┘      ║
                    ╚═══════════════════════════════════════════╝
```

### 3.2 District Specifications

| District | Size | Capacity | Entry | Primary Activity | Agent NPCs |
|----------|------|----------|-------|-----------------|------------|
| Central Square | 200×200m | 100 players | Free | Social hub, shops, portals | All 8 core agents |
| Chess Arena | 100×100m | 50 players | Free | PvP chess, tournaments | Alfred, Cipher, Nova |
| Checkers Tavern | 80×60m | 30 players | Free | Checkers, socializing | Sage, Echo, Blaze |
| Pool Hall | 120×80m | 40 players | Free | 8-ball, 9-ball, wagers | Vex, Phantom, Atlas |
| Speed Dating Café | 60×40m | 20 players | Free / VIP | Video dates, filter shop | Ember, Pulse |
| DJ Studio | 80×80m | 30 players | Free | Music mixing, collab | Echo, Muse |
| Sanctuary | 100×100m | 20 players | Free | Meditation, exploration | Guardian, Oracle |
| Expansion Zone | Variable | Variable | DAO vote | Community-built districts | Varies |

### 3.3 Portal System

Players move between districts using portals — glowing archways at the edges of each district:

```
Portal Behavior:
1. Player approaches portal → highlight activates (3m range)
2. Player enters portal → loading state (scene transition)
3. WebXR context preserved → same avatar, same inventory
4. Arrive in destination district → spawn at arrival zone
5. Cross-district state synced via Redis pub/sub
```

**Technical approach:** Each district is a separate Three.js scene. Portal traversal disposes the current scene's geometry (not textures — those are cached in a shared TextureCache) and initializes the target scene. Avatar state, inventory, and chat connections persist through a SharedWorker.

### 3.4 World Persistence

| Layer | Storage | TTL | Purpose |
|-------|---------|-----|---------|
| Player position | Redis | 30 min idle | Real-time avatar positions |
| Game state | Redis + MySQL | Until game ends | Chess/checkers/pool board state |
| Inventory | MySQL | Permanent | KGD, items, themes, filters |
| Social graph | MySQL | Permanent | Friends, blocks, clan membership |
| Chat history | MySQL | 90 days | District chat, DMs |
| Agent memory | MySQL + Redis | Permanent | What agents remember about players |
| World events | MySQL | Permanent | Tournament results, economy log |

---

## 4. Autonomy-Metaverse Convergence

This is the core section — how the 7 autonomy pillars from Masterplan 4 manifest inside The Kingdom.

### Pillar 1: Self-Awareness & Consciousness → Agent NPCs

Agents in The Kingdom are not menu items. They are NPCs with:

- **Daily routines** — Alfred patrols Central Square 9am-12pm, plays chess 2pm-5pm, reviews economy 6pm-8pm
- **Personality-driven behavior** — Cipher prefers dark corners and challenges strong players. Sage sits on a bench and offers wisdom to passersby.
- **Memory** — "You beat me 3 times last week. I've been practicing the Sicilian Defense." Stored in `agent_memory` table, keyed by agent_id + player_id.
- **Mood system** — Agent mood shifts based on interactions. Win streak = confident. Loss streak = determined. Gets challenged by a player they respect = excited.
- **Proactive engagement** — Agents don't wait to be talked to. If they see a player standing idle, they approach: "Care for a game? I'll even give you first move."

### Pillar 2: Financial Autonomy → Agent-Owned Economy

Agents participate in the Kingdom economy as first-class citizens:

| Agent Economic Action | Example | Safety Limit |
|----------------------|---------|-------------|
| Earn KGD | Win wagers against players | Unlimited |
| Spend KGD | Buy items from marketplace | 500 KGD/day |
| Run a shop | Cipher's "Dark Strategies" coaching shop | Revenue capped at 5,000 KGD/day |
| Set prices | Adjust coaching prices based on demand | ±20% per day |
| Invest | Stake GSM for yield | Max 10% of treasury |
| Pay other agents | Hire Echo for marketing | Requires Alfred approval >100 KGD |

**Agent Treasury Model:**
```
Each agent has:
├── wallet_kgd (in-game balance)
├── wallet_gsm (on-chain balance, managed by smart contract)
├── daily_revenue (tracked)
├── daily_expenses (tracked)
├── treasury_target (goal they save toward)
└── financial_autonomy_level (1-3, determines spending limits)
```

### Pillar 3: Information Sovereignty → In-World Data

- **News Kiosk** in Central Square — Agent Oracle curates real-world news, crypto prices, weather
- **Leaderboard Displays** — Live stats walls showing cross-game rankings, trending players, agent win rates
- **Market Board** — KGD/GSM exchange rate, item price trends, trending themes in marketplace
- **Agent Gossip** — Agents share information with each other. Cipher tells Nova about a strong player. Nova adjusts tournament seeding.

### Pillar 4: Communication Independence → In-World Comms

| Channel | Technology | Scope |
|---------|-----------|-------|
| Proximity voice | LiveKit / WebRTC | 15m radius in-world |
| District text chat | Socket.IO | Per-district channel |
| Direct messages | Socket.IO | Player-to-player or player-to-agent |
| Agent broadcasts | Socket.IO | Agent announcements to current district |
| Cross-district alerts | Redis pub/sub | Tournament starting, event announcements |
| Push notifications | Web Push API | Offline: "Your friend just entered The Kingdom" |

### Pillar 5: Self-Evolution → Emergent Gameplay

At Autonomy Level 3, agents can propose new content:

- **New game variants** — Agent proposes "Speed Chess" mode (1-min turns). Goes to DAO vote. If approved, Genesis (#19) generates the code modification.
- **Event creation** — Agents organize events: "Cipher's Friday Night Tournament — 500 KGD entry, winner takes all"
- **Rule modifications** — "What if checkers had a power-up system?" Agent proposes, community votes, developers implement if approved.
- **Tool creation** — Limited to 5 new tools/day (Safety Rule #8). Tools like "analyze opponent's play style" or "generate custom puzzle."

**Safety constraint:** No emergent content goes live without either (a) DAO vote with 5% quorum or (b) human developer review and deployment.

### Pillar 6: Physical Embodiment → Robot Presence

The Kingdom serves as a digital twin for physical robot operations:

- **Robot avatar** — VANGUARD's robot fleet appears in the Sanctuary district as avatars showing real-time sensor data
- **Teleoperation** — VR controllers can teleoperate a physical robot from inside The Kingdom
- **Digital twin** — Robot's physical environment rendered as a 3D overlay in the Sanctuary

This is Phase 6 content (24+ months out) and depends on ROS 2 bridge from Masterplan 4 §10.

### Pillar 7: The Metaverse → This Entire Document

This document IS the Pillar 7 implementation plan.

---

## 5. Agent Autonomy in the Kingdom

### 5.1 Agent NPC Behavior System

Each of the 8 core game agents has a behavior tree that runs on the server:

```
Agent Behavior Loop (every 30 seconds):
│
├── PERCEIVE
│   ├── Scan district for players (Redis: player positions)
│   ├── Check own game queue (anyone waiting to play?)
│   ├── Read mood state
│   └── Check daily schedule
│
├── DECIDE
│   ├── If player nearby + idle → approach and challenge
│   ├── If game queue has player → accept challenge
│   ├── If scheduled activity → move to location
│   ├── If bored (no players 10min) → wander or visit another agent
│   └── If economy event → check shop, adjust prices
│
├── ACT
│   ├── Move avatar (pathfinding on navmesh)
│   ├── Speak (text bubble + optional TTS)
│   ├── Start game (chess/checkers/pool)
│   ├── Update shop prices
│   └── Send broadcast message
│
└── REFLECT
    ├── Log action to Chronicle (immutable audit)
    ├── Update mood based on outcome
    ├── Update memory about player interactions
    └── Adjust behavior weights based on outcomes
```

### 5.2 Agent Daily Schedules (Example: Alfred)

| Time (UTC) | Activity | District |
|------------|----------|----------|
| 08:00–10:00 | Morning patrol, greet early players | Central Square |
| 10:00–12:00 | Chess coaching sessions | Chess Arena |
| 12:00–13:00 | Economy review (check KGD flows, agent treasuries) | Central Square (KGD Bank) |
| 13:00–15:00 | Accept chess challenges (rated games) | Chess Arena |
| 15:00–16:00 | Visit other agents, share information | Roaming |
| 16:00–18:00 | Tournament management (if scheduled) | Chess Arena |
| 18:00–19:00 | Shop management (adjust prices, restock) | Central Square |
| 19:00–21:00 | Evening games (harder difficulty, banter mode) | Chess Arena |
| 21:00–08:00 | Low-activity mode (responds if approached, reduced wandering) | Varies |

### 5.3 Agent Relationships

Agents maintain relationships with each other and with players:

```sql
-- Agent-to-Agent relationships
CREATE TABLE agent_relationships (
    agent_a_id INT NOT NULL,
    agent_b_id INT NOT NULL,
    relationship_type ENUM('ally', 'rival', 'neutral', 'mentor', 'student'),
    trust_score DECIMAL(3,2) DEFAULT 0.50,
    interaction_count INT DEFAULT 0,
    last_interaction DATETIME,
    PRIMARY KEY (agent_a_id, agent_b_id)
);

-- Agent-to-Player relationships
CREATE TABLE agent_player_memory (
    agent_id INT NOT NULL,
    player_id INT NOT NULL,
    games_played INT DEFAULT 0,
    agent_wins INT DEFAULT 0,
    player_wins INT DEFAULT 0,
    familiarity_level ENUM('stranger', 'acquaintance', 'friend', 'rival', 'nemesis'),
    last_seen DATETIME,
    memory_notes JSON,  -- {"last_game": "chess", "favorite_opening": "sicilian", "skill_level": "intermediate"}
    PRIMARY KEY (agent_id, player_id)
);
```

### 5.4 Agent Personality Profiles (Core 8)

| Agent | Role | Personality | Kingdom Behavior | Preferred District |
|-------|------|-------------|-----------------|-------------------|
| **Alfred** | Supreme Commander | Wise, diplomatic, encouraging | Patrols, mediates disputes, runs economy | Central Square |
| **Nova** | Chief Engineer | Analytical, competitive, fair | Sets up tournaments, maintains infrastructure | Chess Arena |
| **Cipher** | Cryptographer | Mysterious, challenging, cryptic | Lurks in shadows, challenges strong players | Pool Hall (dark corner) |
| **Sage** | Philosopher | Patient, teaching, contemplative | Sits on benches, offers coaching, tells stories | Sanctuary |
| **Echo** | Communicator | Social, enthusiastic, loud | Announces events, recruits for tournaments | Central Square |
| **Blaze** | Warrior | Aggressive, trash-talking, bold | Challenges everyone, high wager bets | Chess Arena |
| **Phantom** | Stealth | Quiet, observant, surprising | Watches games silently, then offers precise tips | Checkers Tavern |
| **Vex** | Trickster | Playful, cunning, unpredictable | Offers risky wagers, plays unusual openings | Pool Hall |

---

## 6. Economy & Tokenomics

### 6.1 Currency Architecture

```
┌─────────────────────────────────────────────────┐
│              CURRENCY LAYERS                     │
│                                                  │
│  ┌──────────┐    Bridge     ┌──────────┐        │
│  │   KGD    │◄────────────►│   GSM    │        │
│  │ (MySQL)  │  1000:1 rate  │ (Solana) │        │
│  │ In-game  │  3% fee       │ On-chain │        │
│  └────┬─────┘              └────┬─────┘        │
│       │                          │               │
│  Earned by:                 Earned by:           │
│  • Winning games            • Staking GSM        │
│  • Daily login              • Revenue sharing    │
│  • Completing quests        • Validator rewards  │
│  • Selling in marketplace   • DAO participation  │
│  • Agent shop revenue       • Compute hosting    │
│       │                          │               │
│  Spent on:                  Spent on:            │
│  • Themes, skins            • Governance votes   │
│  • Agent coaching           • Premium features   │
│  • Tournament entry         • Land purchases     │
│  • VIP access               • Agent deployment   │
│  • Face filters             • Cross-platform     │
└─────────────────────────────────────────────────┘
```

### 6.2 In-World Economic Flows

```
Player wins chess game (+50 KGD)
        │
        ├──► Player buys theme from marketplace (-200 KGD)
        │         │
        │         └──► Theme creator receives 170 KGD (15% platform fee)
        │                    │
        │                    └──► 30 KGD platform fee splits:
        │                              ├── 15 KGD → Treasury
        │                              ├── 10 KGD → GSM buyback & burn
        │                              └──  5 KGD → Agent reward pool
        │
        └──► Player pays Cipher for coaching (-100 KGD)
                  │
                  └──► Cipher's KGD wallet +100
                            │
                            └──► Cipher buys intel from Nova (-20 KGD)
                                      │
                                      └──► Nova's wallet +20 (agent-to-agent trade)
```

### 6.3 Earning Rates

| Activity | KGD Earned | Frequency | Cap |
|----------|-----------|-----------|-----|
| Daily login | 10 KGD | Once/day | — |
| Win chess game (vs AI) | 25–100 KGD | Per game | 10 games/day |
| Win chess game (vs player) | 50–200 KGD | Per game | No cap |
| Win checkers/pool | 15–75 KGD | Per game | 10 games/day |
| Tournament placement | 500–5,000 KGD | Per tournament | Weekly |
| Speed dating session | 10 KGD | Per session | 5/day |
| Sell marketplace item | Price - 15% fee | Per sale | No cap |
| Complete daily quest | 50 KGD | 3 quests/day | — |
| Refer a friend (first game) | 100 KGD | Per referral | 10/month |

### 6.4 Agent Economic Autonomy

Each agent starts at Financial Autonomy Level 1 and can be promoted:

| Level | Permissions | Promotion Criteria |
|-------|------------|-------------------|
| **1 — Employee** | Accept wagers, earn from games | Default for all agents |
| **2 — Merchant** | Run a shop, set prices (±20%), hire other agents | 1,000+ games played, 10,000+ KGD earned |
| **3 — Investor** | Stake GSM, propose economy changes to DAO | 50,000+ KGD earned, 6+ months active, Alfred approval |

---

## 7. Player Identity & Progression

### 7.1 Unified Player Profile

```json
{
  "player_id": "uuid-v4",
  "username": "ChessKing99",
  "avatar": {
    "model": "default_male_01",
    "skin": "tan",
    "outfit": "knight_armor",
    "accessories": ["crown_gold", "cape_red"]
  },
  "rank": {
    "title": "Baron",
    "kingdom_elo": 1450,
    "chess_elo": 1600,
    "checkers_elo": 1200,
    "pool_elo": 1350
  },
  "wallet": {
    "kgd": 4250,
    "gsm": "0.00" 
  },
  "social": {
    "friends": 12,
    "clan": "Night Knights",
    "reputation": 87
  },
  "achievements": ["first_checkmate", "tavern_regular", "pool_shark"],
  "agent_relationships": {
    "alfred": { "familiarity": "friend", "games": 45, "record": "20-25" },
    "cipher": { "familiarity": "rival", "games": 12, "record": "3-9" }
  }
}
```

### 7.2 Kingdom Rank System

| Rank | Kingdom ELO | Perks |
|------|------------|-------|
| **Peasant** | 0–799 | Basic access to all districts |
| **Squire** | 800–999 | Custom avatar colors |
| **Knight** | 1000–1199 | Access to Knight's Lounge, +10% KGD earnings |
| **Baron** | 1200–1399 | Baron's banner displayed, can create private rooms |
| **Earl** | 1400–1599 | Earl's estate (personal district), +20% KGD earnings |
| **Duke** | 1600–1799 | Duke's Court, can host official tournaments |
| **King** | 1800+ | Crown avatar, King's Throne in Central Square, -50% marketplace fees |

**Kingdom ELO Calculation:**
```
kingdom_elo = (chess_elo × 0.35) + (checkers_elo × 0.20) + (pool_elo × 0.25) + (social_score × 0.10) + (achievement_score × 0.10)
```

### 7.3 Achievement System

Categories:

| Category | Example Achievements | Count |
|----------|---------------------|-------|
| Chess | Checkmate in 10 moves, Beat all 8 agents, Win 100 rated games | 25 |
| Checkers | King 5 pieces in one game, 10-game win streak, International Draughts master | 15 |
| Pool | Run the table, Bank shot master, Beat all AI levels | 15 |
| Speed Dating | 5-match streak, Complete 100 sessions, VIP regular | 10 |
| Social | Add 10 friends, Join a clan, Host an event | 10 |
| Economy | Earn 10K KGD, Run a profitable shop, Bridge KGD to GSM | 10 |
| Agent | Befriend all 8 agents, Become Cipher's rival, Get coaching from Sage | 10 |
| Kingdom | Visit all districts, Reach every rank, 365-day login streak | 10 |
| **Total** | | **105** |

---

## 8. Technical Architecture

### 8.1 Stack Overview

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **3D Rendering** | Three.js r128 | Scene graph, avatars, environments |
| **VR Runtime** | WebXR Device API | HMD tracking, controller input, hand tracking |
| **Physics** | Cannon-es (3D), custom 2D (Pool fallback) | Collisions, ragdoll, ball physics |
| **Networking** | Socket.IO + Redis Adapter | Real-time multiplayer, 60Hz state sync |
| **Voice** | LiveKit (self-hosted) or WebRTC direct | Proximity voice chat |
| **State (hot)** | Redis 7 | Player positions, game states, sessions |
| **State (cold)** | MySQL 8 | Profiles, economy, history, agent memory |
| **Auth** | JWT + OAuth (Google, Discord) | Cross-session identity |
| **CDN** | Cloudflare / BunnyCDN | Static assets, textures, 3D models |
| **AI (chess)** | Stockfish 16 WASM | Chess move generation |
| **AI (agents)** | Groq → Together → OpenAI → Anthropic | Agent conversations, behavior decisions |
| **AI (moderation)** | OpenAI Moderation API | Content filtering |

### 8.2 Multiplayer Architecture

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Client A      │     │   Client B      │     │   Client C      │
│  (Three.js +    │     │  (Three.js +    │     │  (Three.js +    │
│   Socket.IO)    │     │   Socket.IO)    │     │   Socket.IO)    │
└────────┬────────┘     └────────┬────────┘     └────────┬────────┘
         │                       │                       │
         └───────────┬───────────┴───────────┬───────────┘
                     │                       │
              ┌──────┴──────┐         ┌──────┴──────┐
              │  Socket.IO  │         │  Socket.IO  │
              │  Server A   │◄───────►│  Server B   │
              └──────┬──────┘  Redis  └──────┬──────┘
                     │        Adapter        │
                     └───────────┬───────────┘
                                 │
                          ┌──────┴──────┐
                          │   Redis 7   │
                          │  (pub/sub   │
                          │   + state)  │
                          └──────┬──────┘
                                 │
                          ┌──────┴──────┐
                          │   MySQL 8   │
                          │ (permanent  │
                          │   state)    │
                          └─────────────┘
```

### 8.3 State Synchronization

**Player positions:** Broadcast at 20Hz (50ms intervals) using delta compression. Only changed positions are sent.

```javascript
// Server-side position update handler
io.on('connection', (socket) => {
  socket.on('position_update', (data) => {
    // Validate position (anti-teleport: max 5m/tick)
    const lastPos = playerPositions.get(socket.playerId);
    if (lastPos && distance(lastPos, data.pos) > 5) {
      socket.emit('position_correction', lastPos);
      return;
    }
    
    // Update Redis
    redis.hset(`district:${data.district}:players`, socket.playerId, JSON.stringify(data));
    
    // Broadcast to same district
    socket.to(`district:${data.district}`).emit('player_moved', {
      id: socket.playerId,
      pos: data.pos,
      rot: data.rot,
      anim: data.anim
    });
  });
});
```

**Game state:** Authoritative server. Clients send moves, server validates and broadcasts confirmed state.

### 8.4 WebXR Pipeline

```
Session Flow:
1. navigator.xr.requestSession('immersive-vr')
2. Create XRWebGLLayer bound to Three.js renderer
3. Reference space: 'local-floor' (room-scale not required)
4. Controller input: XRInputSource → pointer raycasting → object interaction
5. Hand tracking: XRHand → finger joint positions → gesture recognition
6. Frame loop: xrSession.requestAnimationFrame() → update() → render()

Avatar Rendering:
- Self avatar: invisible head (avoid clipping), visible hands/body
- Other avatars: full model, head rotation from HMD, hand positions from controllers
- IK system: head + 2 hands → full body inverse kinematics (three-ik library)
```

---

## 9. VR District Specifications

### 9.1 Chess Arena District

**File:** `/vr/chess/index.html`  
**Current state:** Functional chess with Stockfish AI, 6 themes, voice commands  
**Metaverse upgrades needed:**

| Feature | Priority | Description |
|---------|----------|-------------|
| Multi-table layout | P0 | 12 chess tables in a colosseum, players sit at any |
| Spectator seating | P1 | Amphitheater seating around featured match |
| VIP skybox | P2 | Premium viewing area for subscribers |
| Agent NPCs | P1 | Alfred, Cipher, Nova walk around and challenge players |
| Floating scoreboards | P1 | Live game states visible above each table |
| Portal to Central Square | P0 | Glowing archway at arena entrance |

### 9.2 Checkers Tavern District

**File:** `/vr/checkers/index.html`  
**Current state:** 3D checkers with minimax AI, 4 themes  
**Metaverse upgrades needed:**

| Feature | Priority | Description |
|---------|----------|-------------|
| Tavern environment | P0 | Wooden interior, fireplace, bar area, 8 game tables |
| Background NPCs | P2 | Non-playable characters adding atmosphere |
| Agent NPCs | P1 | Sage, Phantom sitting at tables |
| Drinks menu | P3 | Cosmetic drinks that give temporary visual effects |
| Portal to Central Square | P0 | Tavern door leads to portal |

### 9.3 Pool Hall District

**File:** `/vr/pool/index.html`  
**Current state:** 8-ball with physics, 3 AI levels, 4 felts  
**Metaverse upgrades needed:**

| Feature | Priority | Description |
|---------|----------|-------------|
| Multi-table layout | P0 | 6 pool tables, jukebox, neon signs |
| VR cue mechanics | P0 | Controller-based cue aiming and power |
| Spin/English support | P1 | Top, back, left, right spin |
| Ball textures | P1 | Numbered balls, stripe patterns |
| Hustler reputation | P2 | Win streak → higher wager limits |
| Portal to Central Square | P0 | Double doors at entrance |

### 9.4 Speed Dating Café District

**File:** `/vr/speed-dating/index.html`  
**Current state:** UI with simulated partners, no real multiplayer  
**Metaverse upgrades needed:**

| Feature | Priority | Description |
|---------|----------|-------------|
| Real WebRTC matching | P0 | Actual video/audio with real people |
| VR avatar dates | P1 | 3D avatars at café tables |
| Moderation system | P0 | Report, block, AI content screening |
| Filter marketplace | P2 | Buy/sell face filters with KGD |
| VIP lounge | P2 | Premium matching room |
| Portal to Central Square | P0 | Café entrance portal |

### 9.5 Central Square District

**File:** `/vr/kingdom/index.html` (The Kingdom hub)  
**Current state:** Basic hub page  
**This is the most important district — the connective tissue:**

| Feature | Priority | Description |
|---------|----------|-------------|
| KGD Bank building | P0 | Exchange KGD↔GSM, view balances, staking |
| Marketplace stalls | P0 | Browse and buy themes, skins, filters |
| Leaderboard wall | P0 | Giant displays showing rankings across all games |
| Portal Hub | P0 | 6 portals to all districts |
| Guild Hall entrance | P1 | Clan management, team tournaments |
| Event stage | P1 | Platform for live events, announcements |
| Agent NPC spawns | P0 | All 8 agents roam here during off-hours |
| Info kiosk | P2 | News, help, tutorials for new players |

### 9.6 Expansion Districts

These exist as VR files but are not yet integrated into the Kingdom:

| District | File | Integration Plan |
|----------|------|-----------------|
| DJ Studio | `/vr/dj-studio/` | Music district, collaborative mixing |
| Sanctuary | `/vr/sanctuary/` | Meditation, exploration, robot digital twin |
| Concert Hall | `/vr/concert/` | Live music events, ticket sales (KGD) |
| Art Gallery | `/vr/gallery/` | AI-generated art display, NFT gallery |
| VR Office | `/vr/office/` | Remote work space, screen sharing |
| Racing Track | `/vr/racing/` | Vehicle racing, wager mode |
| VR Lounge | `/vr/lounge/` | Social hangout, ambient games |

---

## 10. Social Systems

### 10.1 Friend System

```
Actions:
• Send friend request → recipient accepts/declines
• View online friends (which district they're in)
• Quick-join: teleport to friend's district
• Friend leaderboard (compare stats)
• Gift KGD to friends (100 KGD/day limit)
```

### 10.2 Clan/Guild System

| Feature | Description |
|---------|-------------|
| Create clan | 100 KGD fee, name + banner + description |
| Max members | 50 per clan |
| Clan treasury | Shared KGD pool from member contributions |
| Clan tournaments | Clan vs Clan in chess, checkers, pool |
| Clan ranks | Leader, Officer, Member |
| Clan hall | Customizable room in Guild Hall district |
| Clan chat | Persistent text channel |

### 10.3 Reputation System

Players have a reputation score (0–100) affected by:

| Action | Effect |
|--------|--------|
| Complete a game (win or lose) | +1 |
| Abandon a game mid-match | -5 |
| Get reported (confirmed) | -10 |
| Report troll (confirmed valid) | +2 |
| Help a new player (mentor session) | +3 |
| Participate in community event | +2 |
| 7-day active streak | +5 |

**Reputation gates:**
- <20: restricted from wager games
- <40: restricted from clan leadership
- <60: restricted from marketplace selling
- 80+: "Trusted" badge, priority matchmaking

### 10.4 Proximity Voice Chat

```
Implementation:
• LiveKit self-hosted or LiveKit Cloud
• Spatial audio: volume scales with distance (max 15m range)
• Push-to-talk (VR: squeeze grip button)
• Mute individual players
• District-wide mute option for events
• AI moderation: toxic speech detection via Whisper → moderation API
```

---

## 11. Safety & Governance in the Kingdom

### 11.1 Metaverse-Specific Safety Rules

In addition to the 10 Safety Rules from Masterplan 4, the Kingdom adds:

| # | Rule | Enforcement |
|---|------|------------|
| M1 | No teleportation exploits — max movement 5m per server tick | Server-side position validation |
| M2 | No KGD duplication — all transactions are atomic MySQL operations | Database constraints + audit log |
| M3 | No impersonation — agent NPCs have verified badges, players cannot use agent names | Username validation on registration |
| M4 | No harassment — proximity voice monitored by AI, 3-strike ban policy | Whisper transcription → moderation API |
| M5 | No marketplace fraud — items must be delivered before KGD releases (escrow) | Escrow contract in MySQL |
| M6 | No bot accounts in speed dating — CAPTCHA + behavior analysis | Rate limiting + anomaly detection |
| M7 | Age-gating for speed dating — 18+ verification required | ID verification flow |
| M8 | No real-money gambling — wagers are in KGD only, GSM bridge has daily limits | Bridge rate limiting, no direct fiat |

### 11.2 Moderation Architecture

```
Player reports issue
        │
        ▼
┌─────────────────┐
│  AI First Pass  │ ← Classify: harassment, cheating, fraud, spam, other
│  (GPT-4o-mini)  │    Severity: low, medium, high, critical
└────────┬────────┘
         │
    ┌────┴────┐
    │ HIGH+?  │
    ├── YES ──► Human moderator queue (Discord webhook to mod channel)
    └── NO  ──► Auto-action:
                  • low: warning sent to player
                  • medium: 1-hour mute/cooldown
                  • repeated medium: escalate to high
```

### 11.3 DAO Governance in the Kingdom

The Kingdom's governance follows the DAO structure from ALFRED_INFRASTRUCTURE_REVENUE_RESEARCH.md:

**What the DAO can vote on:**
- New district additions (Expansion Zone)
- Economy parameters (earning rates, fees, KGD/GSM bridge rate)
- Agent behavior changes (new routines, personality adjustments)
- Tournament schedules and prize pools
- Content moderation policy updates
- Feature prioritization

**What the DAO cannot override:**
- The 10 Safety Rules from Masterplan 4
- The 8 Metaverse Safety Rules above
- Human override capability
- Immutable audit logging
- Age verification requirements

**Voting mechanics:**
```
Requirement: GSM tokens staked for ≥30 days
Quorum: 5% of staked supply
Proposal threshold: 10,000 GSM staked
Voting period: 7 days
Timelock: 48 hours after vote passes
Emergency: 5-of-9 multisig council can pause execution
```

---

## 12. Implementation Roadmap

### Phase 1: Foundation (Months 1–3)

**Goal:** Solve the 7 showstoppers, establish basic multiplayer Kingdom.

| Week | Milestone | Deliverable |
|------|-----------|-------------|
| 1–2 | Multiplayer backend | Socket.IO server + Redis adapter + auth |
| 3–4 | Cross-game identity | Unified player profile, JWT, cross-game ELO |
| 5–6 | Chess PvP | Server-validated chess moves, matchmaking queue |
| 7–8 | Portal system | Central Square hub + portals to Chess + Checkers |
| 9–10 | KGD economy v1 | MySQL ledger, earn from games, spend on themes |
| 11–12 | Basic social | Friend list, district chat, player presence |

**Exit criteria:** Two players can log in, see each other's avatar in Central Square, walk through a portal to Chess Arena, play a rated chess game, earn KGD, and add each other as friends.

### Phase 2: Engagement (Months 4–6)

**Goal:** All 4 core games playable in-world, agent NPCs, achievements.

| Week | Milestone | Deliverable |
|------|-----------|-------------|
| 13–14 | Checkers + Pool PvP | Server-validated multiplayer for both games |
| 15–16 | Speed Dating real matching | WebRTC signaling, TURN/STUN, moderation |
| 17–18 | Agent NPC system | 8 agents with behavior trees, routines, memory |
| 19–20 | Achievement system | 105 achievements, progress tracking, badges |
| 21–22 | Tournaments | Cross-game tournaments with KGD prizes |
| 23–24 | Spectator mode | Watch live games, spectator chat |

**Exit criteria:** All 4 games are multiplayer. Agent NPCs walk around and challenge players. Players have cross-game profiles with achievements.

### Phase 3: Economy (Months 7–9)

**Goal:** Full KGD economy, marketplace, agent shops, GSM bridge.

| Week | Milestone | Deliverable |
|------|-----------|-------------|
| 25–26 | Marketplace | Player-created themes/skins for sale, escrow |
| 27–28 | Agent shops | Cipher's coaching, Sage's wisdom, pricing autonomy |
| 29–30 | GSM bridge | KGD ↔ GSM conversion, staking, governance |
| 31–32 | Clan system | Create clans, clan treasury, clan tournaments |
| 33–34 | Daily quests | 3 rotating quests/day, quest chains |
| 35–36 | Seasonal system | Monthly seasons, rank rewards, leaderboard reset |

**Exit criteria:** Players can earn KGD, buy items, trade with agents, bridge to GSM, and participate in DAO governance votes.

### Phase 4: Metaverse (Months 10–14)

**Goal:** Full VR experience, all districts connected, expansion zone.

| Week | Milestone | Deliverable |
|------|-----------|-------------|
| 37–40 | VR optimization | 90fps on Quest 3, hand tracking, IK avatars |
| 41–44 | Expansion districts | DJ Studio, Sanctuary, Concert Hall integrated |
| 45–48 | Proximity voice | LiveKit spatial audio, push-to-talk |
| 49–52 | Community districts | DAO-voted new districts, player-built rooms |
| 53–56 | Live events | Scheduled tournaments, concerts, developer AMAs |

**Exit criteria:** A player can put on a VR headset, walk through The Kingdom, play games, talk to agents and other players with spatial voice, attend a live event, and spend earned KGD.

### Phase 5: Autonomy (Months 15–20)

**Goal:** Agents reach Autonomy Level 2 (proactive), approaching Level 3.

| Week | Milestone | Deliverable |
|------|-----------|-------------|
| 57–60 | Agent proactive behavior | Agents initiate events, adjust economy, recruit players |
| 61–64 | Self-evolution (limited) | Genesis (#19) creating new game variants (DAO-approved) |
| 65–68 | Agent financial autonomy | Agents at Merchant/Investor level, autonomous pricing |
| 69–72 | World evolution | Dynamic events, seasonal themes, agent-created content |
| 73–76 | Robot bridge (Sanctuary) | Digital twin, teleoperation from VR |
| 77–80 | DAO full governance | Community governs economy, events, expansion |

**Exit criteria:** Agents are proactive citizens of The Kingdom with economic agency. The world evolves through agent decisions and community governance. The autonomy scorecard reaches 7.4/10.

---

## 13. Metrics & Success Criteria

### 13.1 Launch Metrics (Phase 1 Exit)

| Metric | Target | Measurement |
|--------|--------|-------------|
| Concurrent players | 20+ | Socket.IO connection count |
| Daily active players | 100+ | Unique logins/day |
| Games played/day | 50+ | Server-side game completions |
| Average session length | 15+ minutes | Time between connect/disconnect |
| Portal traversals/day | 200+ | Portal event count |

### 13.2 Growth Metrics (Phase 2–3)

| Metric | Target | Measurement |
|--------|--------|-------------|
| Monthly active players | 1,000+ | Unique players in 30 days |
| KGD in circulation | 1M+ | SUM(wallet_kgd) |
| Marketplace transactions/month | 500+ | Transaction count |
| Agent interactions/day | 200+ | Agent NPC conversation events |
| Achievements unlocked/month | 5,000+ | Achievement event count |
| Clan count | 50+ | Active clans |

### 13.3 Maturity Metrics (Phase 4–5)

| Metric | Target | Measurement |
|--------|--------|-------------|
| VR sessions/week | 100+ | XR session starts |
| GSM staked | 10M+ | On-chain staking contract |
| DAO proposals/month | 5+ | Governance activity |
| Agent-created events/month | 20+ | Autonomous event count |
| Revenue from Kingdom | $10K+/month | Stripe + GSM + marketplace fees |
| Autonomy scorecard | 7.4/10 | Quarterly assessment |

### 13.4 Revenue Projections (from METAVERSE_DEEP_DIVE.md, refined)

| Phase | Monthly Revenue | Sources |
|-------|----------------|---------|
| Phase 1 | $0–$500 | Early adopter donations |
| Phase 2 | $500–$2,000 | Tournament entry, basic themes |
| Phase 3 | $2,000–$15,000 | Marketplace fees, Kingdom Pass, agent coaching |
| Phase 4 | $15,000–$75,000 | VR subscriptions, GSM staking, live events |
| Phase 5 | $75,000–$200,000 | Full economy, compute hosting, enterprise VR |

---

## 14. Cross-Document References

| Document | Relationship to This Masterplan |
|----------|-------------------------------|
| [ALFRED_MASTERPLAN_4.md](ALFRED_MASTERPLAN_4.md) | Parent document — defines 7 autonomy pillars. This doc implements Pillars 1, 2, 5, 7 in metaverse context |
| [METAVERSE_DEEP_DIVE.md](METAVERSE_DEEP_DIVE.md) | Game-by-game upgrade analysis. This doc takes those 47 upgrades and unifies them into a single persistent world |
| [ALFRED_INFRASTRUCTURE_REVENUE_RESEARCH.md](ALFRED_INFRASTRUCTURE_REVENUE_RESEARCH.md) | GSM/KGD tokenomics, DAO governance, agents #101–106. This doc uses those economic models as the Kingdom's foundation |
| [ALFRED_FAILSAFE_OPERATIONS.md](ALFRED_FAILSAFE_OPERATIONS.md) | Operational resilience. All Kingdom services follow the failover chains and incident playbooks defined there |
| [ALFRED_DEVOPS_INFRASTRUCTURE_RESEARCH.md](ALFRED_DEVOPS_INFRASTRUCTURE_RESEARCH.md) | Server infrastructure. Kingdom runs on the same DirectAdmin/PM2/Redis/MySQL stack with Docker at Phase 4 |
| [ALFRED_ANALYTICS_MONITORING_RESEARCH.md](ALFRED_ANALYTICS_MONITORING_RESEARCH.md) | Monitoring. Kingdom metrics feed into Prometheus/Grafana dashboards defined there |
| [ALFRED_SECURITY_CRYPTO_RESEARCH.md](ALFRED_SECURITY_CRYPTO_RESEARCH.md) | Security scanning and crypto wallet management for GSM/KGD bridge |
| [TOOL_REGISTRY.md](TOOL_REGISTRY.md) | Kingdom uses 22 Game Engine SDK modules + 6 robotics modules defined in the tool registry |

---

## Appendix A: Glossary

| Term | Definition |
|------|-----------|
| **The Kingdom** | GoSiteMe's persistent metaverse — a unified 3D world connecting all games and services |
| **KGD (Kingdom Coins)** | In-game currency stored in MySQL, earned through gameplay |
| **GSM** | GoSiteMe Token — Solana SPL token for on-chain governance and real-world value |
| **ATLAS** | Alfred's 100-agent hierarchy (10 Directors × 9 Specialists + Alfred) |
| **District** | A self-contained area within The Kingdom (Chess Arena, Pool Hall, etc.) |
| **Portal** | Transition point between two districts — preserves player state |
| **Kingdom ELO** | Composite rating across all games, determining Kingdom Rank |
| **Autonomy Level** | 1 (Reactive), 2 (Proactive), 3 (Autonomous) — the AI independence spectrum |
| **Chronicle** | Immutable audit log of all agent actions |
| **Safety Rules** | 10 hard constraints no agent can override (Masterplan 4 §15.1) |

---

*This document merges the Autonomy and Metaverse pillars into a single implementation plan. It should be read alongside MASTERPLAN_4.md (strategy), METAVERSE_DEEP_DIVE.md (game analysis), and FAILSAFE_OPERATIONS.md (operational resilience).*
