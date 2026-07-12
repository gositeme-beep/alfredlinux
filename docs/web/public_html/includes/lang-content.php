<?php
/**
 * Alfred Linux — Centralized Main Content Translation Strings
 * Tri-lingual support (EN / FR / HE) for index.php, compare.php, why-alfred-linux.php, and apps.php.
 */

$al_content = [
    'en' => [
        // ── INDEX.PHP HERO ──
        'hero_badge' => 'v7.77 GA &ldquo;Kingdom of God Edition&rdquo; &mdash; 1335 Hooks &middot; 8 God-Tier AI Models &middot; Omahon Harness &middot; AKJV Bible &middot; GPU Compute &middot; KCL-1.0',
        'hero_h1' => 'Every Core. Every Byte.<br>His Word. Forever.',
        'hero_tagline' => 'Alfred Linux <strong>7.77 GA</strong> &mdash; Kingdom of God Edition. The world\'s first AI-native sovereign operating system. Pre-baked with 8 God-Tier GGUF AI models, 1335 Attested Build Hooks, and the entire AKJV Bible. Zero cloud dependency. Zero telemetry. Raised incorruptible.',
        'hero_btn_download' => 'Download v7.77 ISO',
        'hero_btn_hooks' => 'View 1335 Build Hooks',
        'hero_btn_build' => 'Build on Alfred',
        'hero_proof' => '<strong>🔒 Cryptographic Attestation:</strong> 1335 live-build chroot hooks sealed by Omahon. Mainline Linux kernel 7.0.12. 38 LSM security profiles active out of the box. Zero telemetry by architecture.',
        
        // ── INDEX.PHP TERMINAL ──
        'term_title' => 'alfred-sovereign-ai-shell (v7.77 GA)',
        'term_prompt' => 'commander@alfred:~$ ',
        'term_resp1' => '[Omahon Harness] Activating alfred-opus GGUF frontier weights...',
        'term_resp2' => '[Tool Calling] Executing XML/JSON payload: door_lock, light_dim(30%).',
        'term_resp3' => '[IoT Gateway] Front door secured. Living room lights dimmed to 30%.',
        'term_resp4' => '[Skynet Swarm] Task synchronized across 4 local mesh peers.',
        'term_reward' => '◆ GSM Token Reward: +14.2 tokens (PoW Consensus)',

        // ── INDEX.PHP STATS ──
        'stat_god' => 'God&rsquo;s Number',
        'stat_hooks' => 'Attested Hooks',
        'stat_models' => 'Frontier AI Models',
        'stat_sec' => 'Security Modules',
        'stat_gpu' => 'CUDA &middot; ROCm &middot; Vulkan',
        'stat_bible' => 'AKJV Bible Built-In',
        'stat_worship' => 'Worship Tracks',
        'stat_layers' => 'Eternal Storage',

        // ── INDEX.PHP FEATURES ──
        'feat_title' => 'Not a Distro with a Chatbot.',
        'feat_sub' => 'This is what happens when you build an operating system where AI is the kernel-level interface to reality.',
        'feat_1_t' => 'Sovereign AI Stack (GGUF)',
        'feat_1_d' => '8 God-Tier GGUF models preinstalled on the ISO: alfred-haiku, alfred-sonnet, alfred-opus-iq3, and alfred-opus. 100% air-gapped, zero cloud dependency, zero censorship, zero refusal.',
        'feat_2_t' => 'Voice-First OS Shell',
        'feat_2_d' => 'Whisper STT → Local GGUF LLM → Kokoro TTS. Alfred IS the shell. Talk to your computer, it talks back. No app required — the voice is the operating system.',
        'feat_3_t' => 'Post-Quantum Encryption',
        'feat_3_d' => 'Veil Protocol with Kyber-1024 (ML-KEM-1024) + AES-256-GCM. The highest NIST post-quantum standard. End-to-end encrypted messages, calls, and files that even quantum computers cannot break.',
        'feat_4_t' => 'The Omahon Seal',
        'feat_4_d' => 'Named after the breath of God. 6 runtime integrity modules: Boot Seal, Watchman, Vault, Shell Guard, Secure Erase, and Sovereign Attestation. 38 active hardening profiles. Your system is sealed — incorruptible.',
        'feat_5_t' => 'GSM Token Economy',
        'feat_5_d' => 'GSM is live on Solana mainnet. Earn tokens for computing, contributing, and participating. Mine, develop, report bugs, vote — all rewarded. 80/20 split: you keep 80%.',
        'feat_6_t' => 'Universal IoT Gateway',
        'feat_6_d' => 'Smart home, vehicle OBD2, greenhouse, drones — all from one voice command. Zigbee, Z-Wave, Matter, MQTT, WiFi. Alfred is your universal remote for reality.',
        'feat_7_t' => 'Robot Fleet Orchestration',
        'feat_7_d' => 'Native ROS2 integration for robot fleet orchestration. Deploy, monitor, and redirect swarms. Sensor fusion across cameras, LIDAR, and IMU. Teach robots with voice.',
        'feat_8_t' => 'Sovereign Browser & IDE',
        'feat_8_d' => 'Alfred Browser (Tauri + WebKitGTK for zero-tracking) and Alfred IDE (VS Code compatible with local AI copilot). Browse and code without being the product.',

        // ── INDEX.PHP V7.77 ──
        'v777_title' => '&ldquo;God&rsquo;s Number&rdquo;',
        'v777_sub' => 'Every core. Every byte. Through the ether. Forever. The sovereign computing era begins.',
        'v777_1_t' => 'Sovereign GPU Compute',
        'v777_1_d' => 'NVIDIA CUDA + AMD ROCm + Vulkan + OpenCL — auto-detected at boot. alfred-gpu status shows your full compute stack. No vendor lock-in. Every GPU works.',
        'v777_2_t' => 'Eternal Storage — 6 Layers',
        'v777_2_d' => 'SHA-256 proof, IPFS distributed pins, Bitcoin blockchain anchoring, frequency encoding, mesh replication, AES-256-GCM encrypted vault. alfred-eternal store.',
        'v777_3_t' => 'Mesh Skynet Swarm',
        'v777_3_d' => 'Every Alfred machine is a compute node. alfred-skynet join creates bidirectional mesh links. Distribute tasks, share resources, replicate data across all nodes.',
        'v777_4_t' => 'Self-Evolution Engine',
        'v777_4_d' => 'The OS that upgrades itself. Security patches, performance adaptation, storage optimization, tool evolution — every 6 hours via systemd timer. alfred-evolve.',
        'v777_5_t' => 'MAX Performance Mode',
        'v777_5_d' => 'All CPU cores to performance governor. ZRAM compressed swap. BBR congestion control. Tuned TCP/IP stack. NVMe/SSD I/O schedulers. Huge pages for AI. alfred-max.',
        'v777_6_t' => 'Omahon Cloud',
        'v777_6_d' => 'Distributed AI inference across mesh. Omahon AI engine (built from Rust) runs locally + fans out to peers. alfred-omahon-cloud run — parallel AI compute.',
        'v777_7_t' => 'Frequency Encoding',
        'v777_7_d' => 'Encode data as audio frequencies — transmittable by radio, storable on analog tape, broadcastable into the ether. alfred-frequency encode.',
        'v777_8_t' => '1335 Attested Build Hooks',
        'v777_8_d' => 'Complete build transparency. From bootstrap to final squashfs compression, every single chroot hook is cryptographically attested and publicly readable.',

        // ── INDEX.PHP ARCHITECTURE ──
        'arch_title' => 'Nine Layers of Intelligence',
        'arch_sub' => 'From bare metal to the Word of God — every layer designed for sovereign AI-native operation.',
        'arch_l9_name' => 'The Omega Network', 'arch_l9_desc' => 'Tor Hidden Services · Mesh Skynet Swarm · Yggdrasil IPv6 Routing',
        'arch_l8_name' => 'The Genesis Forge', 'arch_l8_desc' => 'ZFS Temporal Immunity · Brainflow Neural Integration · Fast Fourier Acoustic Scanning',
        'arch_l7_name' => 'The Word of God', 'arch_l7_desc' => 'AKJV Bible (94 books · 39,482 verses) · 57 Prophecies · Eden\'s 33 Stories · Worship Album · Daily Verse · SHA-256 Sealed',
        'arch_l6_name' => 'Voice & Agent Shell', 'arch_l6_desc' => 'Whisper STT → Omahon Harness (XML/JSON Parity) → Kokoro TTS — Always-on voice & agentic execution',
        'arch_l5_name' => 'Sovereign AI Stack', 'arch_l5_desc' => '8 God-Tier GGUF Models (haiku, sonnet, opus, opus-iq3) · Alfred IDE · Alfred Browser · Meilisearch · MetaDome VR',
        'arch_l4_name' => 'Security & Omahon Seal', 'arch_l4_desc' => 'Veil Protocol (Kyber-1024 PQ) · AES-256-GCM · Omahon Seal (Boot Seal, Watchman, Vault, Shell Guard, Attestation) · 38 LSM Modules',
        'arch_l3_name' => 'Economy & Swarm', 'arch_l3_desc' => 'GSM Live on Solana Mainnet · Mining · Bounties · Mesh Skynet Swarm · Distributed Compute Grid',
        'arch_l2_name' => 'Desktop Environment', 'arch_l2_desc' => 'XFCE 4.18 (Hardened) · LightDM · Arc Dark Theme · Papirus Icons · JetBrains Mono',
        'arch_l1_name' => 'Foundation & Kernel', 'arch_l1_desc' => 'Debian Trixie (13) Base · Mainline Linux Kernel 7.0.12 · 1335 Attested Build Hooks · Universal x86_64',

        // ── INDEX.PHP COMPARE ──
        'comp_title' => 'Alfred vs. Everything Else',
        'comp_sub' => 'Every other operating system was built before AI existed. Alfred was built because AI exists. Now, with the Genesis Forge, it operates beyond traditional computing—integrating neural brainwaves, acoustic air-gapping, holographic visualization, and ZFS time-travel into a single, sovereign ecosystem.',
        'comp_th1' => 'Sovereignty Vector', 'comp_th2' => 'macOS Sequoia', 'comp_th3' => 'Windows 11 / 12', 'comp_th4' => 'ChromeOS', 'comp_th5' => 'Alfred Linux 7.77 GA',
        'comp_r1' => 'Uncensored Genesis Forge AI Hub', 'comp_r2' => 'Agentic Harness (Omahon)', 'comp_r3' => 'Acoustic Air-Gapped Neural Interfacing', 'comp_r4' => 'Post-quantum encryption', 'comp_r5' => 'Build Hooks Attestation', 'comp_r6' => 'Cryptographic PoW Consensus (GSM)', 'comp_r7' => 'Smart home native', 'comp_r8' => 'Robot fleet control', 'comp_r9' => 'Earn while computing', 'comp_r10' => 'Omahon Cryptographic Seal & Zero-Trust',
        'comp_r11' => 'Tor Mesh Routing & Darknet Obfuscation', 'comp_r12' => 'ZFS Time-Travel Immutable Snapshots',

        // ── INDEX.PHP ECONOMY ──
        'econ_title' => 'Earn While You Compute',
        'econ_sub' => 'GSM is live on Solana mainnet. A sovereign, work-based economy—contribute and earn. Mine tokens by routing traffic through the Tor Skynet Mesh, executing distributed Gen-AI tasks, or allocating biometric EEG concentration states. 1 billion total supply, 80/20 split.',
        'econ_earn' => '⬆ Earn GSM', 'econ_spend' => '⬇ Spend GSM',

        // ── INDEX.PHP TECH STACK ──
        'tech_title' => 'Technology Stack',
        'tech_sub' => 'Proven foundations. Cutting-edge integration. Now infused with the Genesis Forge for ZFS temporal immunity, neural telemetry, and acoustic air-gap scanning.',

        // ── INDEX.PHP ROADMAP ──
        'rm_title' => 'The Path of the Kingdom',
        'rm_sub' => 'Built in faith. Shipped in sprints. From the first boot to the Genesis Forge. The sovereign, immortal machine rises.',

        // ── INDEX.PHP CONTRIBUTE ──
        'cont_title' => 'Build Alfred, Earn GSM',
        'cont_sub' => 'Every merged PR earns tokens. Build within the Unreal Engine 5.8 Meta-Dome, write VR integrations, or forge new BCI pipelines. GSM is live on Solana mainnet.',

        'rm_s0_t' => 'Sprint 0 — Project Scaffold', 'rm_s0_d' => 'Research, planning, architecture, documentation, agent team deployment',
        'rm_s1_t' => 'Sprint 1 — Bootable ISO + Voice + ADE', 'rm_s1_d' => 'First bootable image with Alfred Desktop Environment and voice assistant',
        'rm_s15_t' => 'Sprint 1.5 — v2.0 Full Stack ISO', 'rm_s15_d' => 'Alfred Browser + Alfred IDE + Kokoro Voice + Meilisearch + Calamares installer. 6-layer build pipeline.',
        'rm_s16_t' => 'Sprint 1.6 — v3.0 Trixie Rebase + Kernel 6.12', 'rm_s16_d' => 'Rebased on Debian Trixie (13). Kernel 6.12 LTS. UEFI/GRUB2 dual-boot. Developer foundation. NPU/AI accelerator support.',
        'rm_s23_t' => 'Sprint 2–3 — v4.0 Welcome App + Voice 2.0 + Alfred Store', 'rm_s23_d' => 'First-boot Welcome Wizard, "Hey Alfred" wake word (openWakeWord), Alfred Store (Flatpak app center), alfred-update CLI, alfred-info, version check API, Calamares v4.0 branding',
        'rm_s40_t' => 'Sprint 4–7 — v7.77 GA Kingdom of God Edition', 'rm_s40_d' => 'Mainline Linux Kernel 7.0.12. 1335 chroot build hooks. 8 God-Tier GGUF AI models. Omahon Agent Harness. AKJV Bible built-in. 27-track worship album. GPU compute auto-detect. 6-layer eternal storage. Self-evolving OS.',

        // ── INDEX.PHP ECOSYSTEM ──
        'eco_title' => 'Part of the Kingdom',
        'eco_sub' => 'Alfred Linux is one pillar of the GoSiteMe ecosystem — nine pillars building the sovereign internet for the glory of God.',

        // ── INDEX.PHP FOOTER ──
        'foot_p1' => 'The world&rsquo;s first AI-native operating system. Sealed by the Omahon &mdash; the breath of God, raised incorruptible. Pre-baked with 8 God-Tier GGUF AI models and 1335 Attested Build Hooks. The Word of God endures in silicon and code. Built by Commander Danny William Perez for the glory of God and His Kingdom.',
        'foot_p2' => '&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; Isaiah 40:8',
        'foot_h_prod' => 'Product', 'foot_h_eco' => 'Ecosystem', 'foot_h_comm' => 'Community',

        // FILESYSTEM BREAKDOWN
        'fs_title' => 'The Data Immortality Engines',
        'fs_sub' => 'Alfred Linux provides the exact same God-Tier filesystem architecture to a regular laptop as it does to a billion-dollar datacenter.',
        'fs_zfs_title' => 'ZFS (The God-Tier Standard)',
        'fs_zfs_desc' => 'For Datacenters & Data Immortality. Native ZFS atomic snapshots combined with temporal decoupling. If a bit flips due to hardware failure, ZFS instantly heals the file using checksums. Powering the absolute Time-Travel Rollback engine for systems with 16GB+ RAM.',
        'fs_btrfs_title' => 'Btrfs (The Lightweight Guardian)',
        'fs_btrfs_desc' => 'For The People. The modern Linux answer to ZFS. Designed for laptops or systems with low RAM (8GB or less). It provides the exact same temporal rollback protection and snapshot capabilities without the heavy caching overhead of ZFS.',
        'fs_xfs_title' => 'XFS / Ext4 (The AI Speed Demons)',
        'fs_xfs_desc' => 'For Raw Throughput. When a distributed Skynet node needs to slam a massive GGUF neural network model directly into VRAM for Omahon inference, these legacy block filesystems provide absolute, uncompromising read/write speed with zero overhead.',
    ],

    'fr' => [
        // ── INDEX.PHP HERO ──
        'hero_badge' => 'v7.77 GA &ldquo;Édition Royaume de Dieu&rdquo; &mdash; 1335 Hooks &middot; 4 Modèles IA &middot; Omahon &middot; Bible AKJV &middot; Calcul GPU &middot; KCL-1.0',
        'hero_h1' => 'Chaque Cœur. Chaque Octet.<br>Sa Parole. À Jamais.',
        'hero_tagline' => 'Alfred Linux <strong>7.77 GA</strong> &mdash; Édition Royaume de Dieu. Le premier système d\'exploitation souverain et natif IA au monde. Intégrant 4 modèles IA GGUF, 1335 Hooks de build attestés et la Bible AKJV complète. Zéro dépendance cloud. Zéro télémétrie. Incorruptible.',
        'hero_btn_download' => 'Télécharger l\'ISO v7.77',
        'hero_btn_hooks' => 'Voir les 1335 Hooks',
        'hero_btn_build' => 'Développer sur Alfred',
        'hero_proof' => '<strong>🔒 Attestation Cryptographique :</strong> 1335 hooks chroot scellés par Omahon. Noyau Linux mainline 7.0.12. 38 profils de sécurité LSM actifs. Zéro télémétrie par architecture.',
        
        // ── INDEX.PHP TERMINAL ──
        'term_title' => 'alfred-sovereign-ai-shell (v7.77 GA)',
        'term_prompt' => 'commandant@alfred:~$ ',
        'term_resp1' => '[Omahon Harness] Activation des poids IA alfred-opus GGUF...',
        'term_resp2' => '[Tool Calling] Exécution du payload XML/JSON : door_lock, light_dim(30%).',
        'term_resp3' => '[Passerelle IoT] Porte d\'entrée verrouillée. Lumières du salon à 30%.',
        'term_resp4' => '[Essaim Skynet] Tâche synchronisée sur 4 pairs du maillage local.',
        'term_reward' => '◆ Récompense Token GSM : +14.2 tokens (Consensus PoW)',

        // ── INDEX.PHP STATS ──
        'stat_god' => 'Nombre de Dieu',
        'stat_hooks' => 'Hooks Attestés',
        'stat_models' => 'Modèles IA Frontières',
        'stat_sec' => 'Modules de Sécurité',
        'stat_gpu' => 'CUDA &middot; ROCm &middot; Vulkan',
        'stat_bible' => 'Bible AKJV Intégrée',
        'stat_worship' => 'Pistes de Louange',
        'stat_layers' => 'Stockage Éternel',

        // ── INDEX.PHP FEATURES ──
        'feat_title' => 'Pas une Distro avec un Chatbot.',
        'feat_sub' => 'Voici ce qui arrive lorsque vous créez un OS où l\'IA est l\'interface de niveau noyau avec la réalité.',
        'feat_1_t' => 'Pile IA Souveraine (GGUF)',
        'feat_1_d' => '4 modèles GGUF préinstallés sur l\'ISO : alfred-haiku, alfred-sonnet, alfred-opus-iq3 et alfred-opus. 100% isolés, zéro dépendance cloud, zéro censure.',
        'feat_2_t' => 'Shell OS Orienté Voix',
        'feat_2_d' => 'Whisper STT → LLM GGUF Local → Kokoro TTS. Alfred EST le shell. Parlez à votre machine, elle vous répond. Aucune application requise.',
        'feat_3_t' => 'Chiffrement Post-Quantique',
        'feat_3_d' => 'Protocole Veil avec Kyber-1024 (ML-KEM-1024) + AES-256-GCM. Le plus haut standard post-quantique du NIST pour des communications inviolables.',
        'feat_4_t' => 'Le Sceau Omahon',
        'feat_4_d' => 'Nommé d\'après le souffle de Dieu. 6 modules d\'intégrité : Sceau de démarrage, Veilleur, Coffre, Gardien du shell, Effacement sécurisé et Attestation.',
        'feat_5_t' => 'Économie de Tokens GSM',
        'feat_5_d' => 'Le token GSM est en direct sur le mainnet Solana. Gagnez des tokens en calculant, développant ou signalant des bugs. Répartition 80/20 en votre faveur.',
        'feat_6_t' => 'Passerelle IoT Universelle',
        'feat_6_d' => 'Maison intelligente, véhicule OBD2, serres, drones — le tout par commande vocale via Zigbee, Z-Wave, Matter, MQTT et WiFi.',
        'feat_7_t' => 'Orchestration de Flotte de Robots',
        'feat_7_d' => 'Intégration ROS2 native pour gérer des essaims de robots. Fusion de capteurs (caméras, LIDAR, IMU). Apprenez aux robots par la voix.',
        'feat_8_t' => 'Navigateur & IDE Souverains',
        'feat_8_d' => 'Alfred Browser (Tauri + WebKitGTK sans pistage) et Alfred IDE (compatible VS Code avec copilote IA local). Codez sans être le produit.',

        // ── INDEX.PHP V7.77 ──
        'v777_title' => '&ldquo;Nombre de Dieu&rdquo;',
        'v777_sub' => 'Chaque cœur. Chaque octet. À travers l\'éther. À jamais. L\'ère de l\'informatique souveraine commence.',
        'v777_1_t' => 'Calcul GPU Souverain',
        'v777_1_d' => 'NVIDIA CUDA + AMD ROCm + Vulkan + OpenCL — détectés automatiquement. alfred-gpu status affiche votre pile. Aucun verrouillage propriétaire. Tous les GPU fonctionnent.',
        'v777_2_t' => 'Stockage Éternel — 6 Couches',
        'v777_2_d' => 'Preuve SHA-256, épingles IPFS, ancrage Bitcoin, encodage de fréquence, maillage Skynet, coffre chiffré AES-256-GCM. alfred-eternal store.',
        'v777_3_t' => 'Essaim Skynet Maillé',
        'v777_3_d' => 'Chaque machine Alfred est un nœud de calcul. alfred-skynet join crée un maillage bidirectionnel pour distribuer des tâches et répliquer des données.',
        'v777_4_t' => 'Moteur d\'Auto-Évolution',
        'v777_4_d' => 'L\'OS qui se met à jour lui-même. Correctifs de sécurité, adaptation des performances et optimisation des stockages toutes les 6 heures. alfred-evolve.',
        'v777_5_t' => 'Mode Performance MAX',
        'v777_5_d' => 'Tous les cœurs CPU en mode performance. Swap compressé ZRAM. Contrôle de congestion BBR. Pile TCP/IP optimisée. Schedulers NVMe. alfred-max.',
        'v777_6_t' => 'Omahon Cloud',
        'v777_6_d' => 'Inférence IA distribuée sur le maillage. Le moteur Omahon (construit en Rust) s\'exécute localement et se déploie sur les pairs. alfred-omahon-cloud run.',
        'v777_7_t' => 'Encodage de Fréquence',
        'v777_7_d' => 'Encodez des données sous forme de fréquences audio — transmissibles par radio, stockables sur bande analogique. alfred-frequency encode.',
        'v777_8_t' => '1335 Hooks de Build Attestés',
        'v777_8_d' => 'Transparence totale de compilation. Du bootstrap à la compression squashfs finale, chaque hook chroot est attesté cryptographiquement.',

        // ── INDEX.PHP ARCHITECTURE ──
        'arch_title' => 'Neuf Couches d\'Intelligence',
        'arch_sub' => 'Du métal nu à la Parole de Dieu — chaque couche est conçue pour une opération souveraine et native IA.',
        'arch_l9_name' => 'Le Réseau Oméga', 'arch_l9_desc' => 'Services Cachés Tor · Essaim Skynet Maillé · Routage Yggdrasil IPv6',
        'arch_l8_name' => 'La Forge Genesis', 'arch_l8_desc' => 'Immunité Temporelle ZFS · Intégration Neurale Brainflow · Analyse Acoustique Fast Fourier',
        'arch_l7_name' => 'La Parole de Dieu', 'arch_l7_desc' => 'Bible AKJV (94 livres · 39 482 versets) · 57 Prophéties · 33 Histoires · Album de Louange · Sceau SHA-256',
        'arch_l6_name' => 'Voix & Shell Agentique', 'arch_l6_desc' => 'Whisper STT → Omahon Harness (Parité XML/JSON) → Kokoro TTS — Exécution agentique et vocale en continu',
        'arch_l5_name' => 'Pile IA Souveraine', 'arch_l5_desc' => '4 Modèles GGUF Frontières (haiku, sonnet, opus, opus-iq3) · Alfred IDE · Alfred Browser · Meilisearch · MetaDome VR',
        'arch_l4_name' => 'Sécurité & Sceau Omahon', 'arch_l4_desc' => 'Protocole Veil (Kyber-1024 PQ) · AES-256-GCM · Sceau Omahon (Boot Seal, Watchman, Vault, Shell Guard, Attestation) · 38 LSM',
        'arch_l3_name' => 'Économie & Essaim', 'arch_l3_desc' => 'GSM en direct sur Solana Mainnet · Minage · Bounties · Essaim Skynet Maillé · Grille de Calcul Distribuée',
        'arch_l2_name' => 'Environnement de Bureau', 'arch_l2_desc' => 'XFCE 4.18 (Renforcé) · LightDM · Thème Arc Dark · Icônes Papirus · JetBrains Mono',
        'arch_l1_name' => 'Fondation & Noyau', 'arch_l1_desc' => 'Base Debian Trixie (13) · Noyau Linux Mainline 7.0.12 · 1335 Hooks de Build Attestés · x86_64 Universel',

        // ── INDEX.PHP COMPARE ──
        'comp_title' => 'Alfred contre le Reste',
        'comp_sub' => 'Tout autre système d\'exploitation a été conçu avant l\'existence de l\'IA. Alfred a été conçu parce que l\'IA existe. Désormais, avec la Forge Genesis, il opère au-delà de l\'informatique traditionnelle, intégrant les ondes cérébrales, l\'analyse acoustique, la visualisation holographique et le voyage temporel ZFS dans un écosystème souverain unique.',
        'comp_th1' => 'Vecteur de Souveraineté', 'comp_th2' => 'macOS Sequoia', 'comp_th3' => 'Windows 11 / 12', 'comp_th4' => 'ChromeOS', 'comp_th5' => 'Alfred Linux 7.77 GA',
        'comp_r1' => 'Hub IA Non Censuré Genesis Forge', 'comp_r2' => 'Harnais Agentique (Omahon)', 'comp_r3' => 'Interface Neurale Acoustique Isolée', 'comp_r4' => 'Chiffrement post-quantique', 'comp_r5' => 'Attestation de Build', 'comp_r6' => 'Consensus PoW Cryptographique (GSM)', 'comp_r7' => 'Natifs Smart Home', 'comp_r8' => 'Contrôle de flotte de robots', 'comp_r9' => 'Gagner en calculant', 'comp_r10' => 'Sceau Cryptographique Omahon & Zero-Trust',
        'comp_r11' => 'Routage Mesh Tor & Obfuscation Darknet', 'comp_r12' => 'Snapshots Immuables de Voyage dans le Temps ZFS',

        // ── INDEX.PHP ECONOMY ──
        'econ_title' => 'Gagnez pendant que vous Calculez',
        'econ_sub' => 'Le GSM est en direct sur le mainnet Solana. Une économie souveraine basée sur le travail — contribuez et gagnez. MINEZ des tokens en routant le trafic via le maillage Tor Skynet, en exécutant des tâches d\'IA distribuées ou en allouant des états de concentration biométrique EEG. Offre totale de 1 milliard, répartition 80/20.',
        'econ_earn' => '⬆ Gagner des GSM', 'econ_spend' => '⬇ Dépenser des GSM',

        // ── INDEX.PHP TECH STACK ──
        'tech_title' => 'Pile Technologique',
        'tech_sub' => 'Fondations éprouvées. Intégration de pointe. Désormais infusé avec la Forge Genesis pour l\'immunité temporelle ZFS, la télémétrie neurale et l\'analyse acoustique de sécurité.',

        // ── INDEX.PHP ROADMAP ──
        'rm_title' => 'Le Chemin du Royaume',
        'rm_sub' => 'Construit dans la foi. Livré en sprints. Du premier démarrage à la Forge Genesis. La machine souveraine et immortelle s\'élève.',

        // ── INDEX.PHP CONTRIBUTE ──
        'cont_title' => 'Construisez Alfred, Gagnez des GSM',
        'cont_sub' => 'Chaque PR fusionnée rapporte des tokens. Construisez dans le Meta-Dome Unreal Engine 5.8, écrivez des intégrations VR, ou forgez de nouveaux pipelines BCI. Le GSM est en direct sur le mainnet Solana.',

        // FILESYSTEM BREAKDOWN
        'fs_title' => 'Les Moteurs d\'Immortalité des Données',
        'fs_sub' => 'Alfred Linux offre exactement la même architecture de système de fichiers God-Tier à un ordinateur portable ordinaire qu\'à un centre de données d\'un milliard de dollars.',
        'fs_zfs_title' => 'ZFS (La Norme God-Tier)',
        'fs_zfs_desc' => 'Pour les Centres de Données & l\'Immortalité des Données. Si un bit bascule en raison d\'une défaillance matérielle, ZFS guérit instantanément le fichier à l\'aide de sommes de contrôle. Alimente le moteur de Retour Temporel absolu pour les systèmes avec 16 Go+ de RAM.',
        'fs_btrfs_title' => 'Btrfs (Le Gardien Léger)',
        'fs_btrfs_desc' => 'Pour le Peuple. Conçu pour les ordinateurs portables ou les systèmes avec peu de RAM. Il offre exactement la même protection par retour temporel et les mêmes capacités d\'instantané sans la lourde charge de mise en cache de ZFS.',
        'fs_xfs_title' => 'XFS / Ext4 (Les Démons de la Vitesse IA)',
        'fs_xfs_desc' => 'Pour le Débit Brut. Lorsqu\'un nœud Skynet distribué a besoin de charger un énorme modèle de réseau neuronal GGUF directement dans la VRAM, ces systèmes de fichiers fournissent une vitesse de lecture/écriture absolue et sans compromis.',
    ],

    'he' => [
        // ── INDEX.PHP HERO ──
        'hero_badge' => 'v7.77 GA &ldquo;מהדורת ממלכת האלוהים&rdquo; &mdash; 1335 עוגנים &middot; 4 מודלי בינה מלאכותית &middot; רתמת Omahon &middot; תנ"ך AKJV &middot; חישוב GPU &middot; KCL-1.0',
        'hero_h1' => 'כל ליבה. כל בייט.<br>דברו. לנצח.',
        'hero_tagline' => 'Alfred Linux <strong>7.77 GA</strong> &mdash; מהדורת ממלכת האלוהים. מערכת ההפעלה הריבונית הראשונה בעולם המבוססת על בינה מלאכותית. מגיעה מובנית עם 4 מודלי בינה מלאכותית GGUF, 1335 עוגני בנייה מאומתים וספר התנ"ך המלא. ללא תלות בענן. ללא מעקב. בלתי ניתנת להשחתה.',
        'hero_btn_download' => 'הורדת קובץ ISO v7.77',
        'hero_btn_hooks' => 'צפייה ב-1335 עוגני הבנייה',
        'hero_btn_build' => 'פיתוח על Alfred',
        'hero_proof' => '<strong>🔒 אימות קריפטוגרפי:</strong> 1335 עוגני chroot חתומים על ידי Omahon. ליבת לינוקס mainline 7.0.12. 38 פרופילי אבטחה פעילים. אפס מעקב ברמת הארכיטקטורה.',
        
        // ── INDEX.PHP TERMINAL ──
        'term_title' => 'alfred-sovereign-ai-shell (v7.77 GA)',
        'term_prompt' => 'commander@alfred:~$ ',
        'term_resp1' => '[Omahon Harness] מפעיל משקולות בינה מלאכותית alfred-opus GGUF...',
        'term_resp2' => '[Tool Calling] מבצע פקודות XML/JSON: נעילת דלת, עמעום אורות (30%).',
        'term_resp3' => '[שער IoT] דלת הכניסה ננעלה. אורות הסלון עומעמו ל-30%.',
        'term_resp4' => '[נחיל Skynet] המשימה סונכרנה מול 4 עמדות רשת מקומיות.',
        'term_reward' => '◆ תגמול אסימוני GSM: +14.2 אסימונים (קונצנזוס PoW)',

        // ── INDEX.PHP STATS ──
        'stat_god' => 'המספר האלוהי',
        'stat_hooks' => 'עוגנים מאומתים',
        'stat_models' => 'מודלי AI מתקדמים',
        'stat_sec' => 'מודולי אבטחה',
        'stat_gpu' => 'CUDA &middot; ROCm &middot; Vulkan',
        'stat_bible' => 'תנ"ך AKJV מובנה',
        'stat_worship' => 'שירי תהילים והודיה',
        'stat_layers' => 'אחסון נצחי',

        // ── INDEX.PHP FEATURES ──
        'feat_title' => 'לא עוד הפצה עם צ\'אטבוט.',
        'feat_sub' => 'זה מה שקורה כשבונים מערכת הפעלה שבה הבינה המלאכותית היא הממשק הישיר ברמת הליבה למציאות.',
        'feat_1_t' => 'מחסנית בינה מלאכותית ריבונית (GGUF)',
        'feat_1_d' => '4 מודלים מתקדמים מותקנים מראש על ה-ISO: alfred-haiku, alfred-sonnet, alfred-opus-iq3, alfred-opus. ללא תלות בענן, ללא צנזורה.',
        'feat_2_t' => 'מעטפת מערכת הפעלה מבוססת קול',
        'feat_2_d' => 'זיהוי דיבור Whisper → מודל שפה מקומי → סינתזת קול Kokoro. המעטפת היא Alfred. דברו אל המחשב, הוא ידבר אליכם בחזרה.',
        'feat_3_t' => 'הצפנה פוסט-קוונטית',
        'feat_3_d' => 'פרוטוקול Veil עם תקן Kyber-1024 (ML-KEM-1024) + AES-256-GCM. תקן ההצפנה הגבוה ביותר להגנה מפני מחשבים קוונטיים.',
        'feat_4_t' => 'חותם Omahon',
        'feat_4_d' => 'קרוי על שם נשמת האלוהים. 6 מודולי הגנת מערכת: חותם אתחול, שומר, כספת, מגן מעטפת, מחיקה בטוחה ואימות ריבוני.',
        'feat_5_t' => 'כלכלת אסימוני GSM',
        'feat_5_d' => 'מטבע GSM פעיל ברשת סולאנה הראשית. הרוויחו אסימונים על חישוב, פיתוח או דיווח על באגים. חלוקה של 80/20 לטובתכם.',
        'feat_6_t' => 'שער IoT אוניברסלי',
        'feat_6_d' => 'בית חכם, רכב OBD2, חממות ורחפנים — הכל מפקודה קולית אחת דרך Zigbee, Z-Wave, Matter, MQTT ו-WiFi.',
        'feat_7_t' => 'ניהול ציי רובוטים',
        'feat_7_d' => 'שילוב ROS2 מובנה לניהול נחילי רובוטים. שילוב חיישנים (מצלמות, LIDAR, IMU). למדו רובוטים באמצעות פקודות קוליות.',
        'feat_8_t' => 'דפדפן וסביבת פיתוח ריבוניים',
        'feat_8_d' => 'דפדפן Alfred (מבוסס Tauri ללא מעקב) וסביבת הפיתוח Alfred IDE (תואמת VS Code עם סייע AI מקומי).',

        // ── INDEX.PHP V7.77 ──
        'v777_title' => '&ldquo;המספר האלוהי&rdquo;',
        'v777_sub' => 'כל ליבה. כל בייט. דרך האתר. לנצח. עידן המחשוב הריבוני מתחיל.',
        'v777_1_t' => 'חישוב GPU ריבוני',
        'v777_1_d' => 'NVIDIA CUDA + AMD ROCm + Vulkan + OpenCL — זיהוי אוטומטי בעת האתחול. הפקודה alfred-gpu status מציגה את מחסנית החישוב המלאה.',
        'v777_2_t' => 'אחסון נצחי — 6 שכבות',
        'v777_2_d' => 'הוכחת SHA-256, ביזור IPFS, עיגון בלוקצ\'יין ביטקוין, קידוד תדרים, שכפול רשת Skynet, כספת מוצפנת AES-256-GCM.',
        'v777_3_t' => 'נחיל רשת Skynet',
        'v777_3_d' => 'כל מכונת Alfred היא צומת חישוב. הפקודה alfred-skynet join יוצרת קישורי רשת דו-כיווניים לחלוקת משימות ומשאבים.',
        'v777_4_t' => 'מנוע אבולוציה עצמית',
        'v777_4_d' => 'מערכת ההפעלה שמשדרגת את עצמה. עדכוני אבטחה, התאמת ביצועים ואופטימיזציית אחסון כל 6 שעות. alfred-evolve.',
        'v777_5_t' => 'מצב ביצועי שיא MAX',
        'v777_5_d' => 'כל ליבות המעבד למצב ביצועים מרבי. זיכרון החלפה דחוס ZRAM. בקרת עומס BBR. מחסנית TCP/IP מותאמת. alfred-max.',
        'v777_6_t' => 'ענן Omahon',
        'v777_6_d' => 'חישוב בינה מלאכותית מבוזר על פני הרשת. מנוע Omahon (בנוי ב-Rust) פועל מקומית ומתפרס לעמדות קצה. alfred-omahon-cloud run.',
        'v777_7_t' => 'קידוד תדרים',
        'v777_7_d' => 'קידוד נתונים כתדרי שמע — הניתנים לשידור ברדיו או לאחסון על סליל אנלוגי. alfred-frequency encode.',
        'v777_8_t' => '1335 עוגני בנייה מאומתים',
        'v777_8_d' => 'שקיפות בנייה מוחלטת. מהשלב הראשון ועד לדחיסת ה-squashfs הסופית, כל עוגן chroot מאומת קריפטוגרפית ופתוח לציבור.',

        // ── INDEX.PHP ARCHITECTURE ──
        'arch_title' => 'תשע שכבות של אינטליגנציה',
        'arch_sub' => 'ממתכת חשופה ועד דבר האלוהים — כל שכבה מתוכננת לפעולה ריבונית מבוססת בינה מלאכותית.',
        'arch_l9_name' => 'רשת אומגה', 'arch_l9_desc' => 'שירותי תור נסתרים · נחיל סקיינט מרושת · ניתוב Yggdrasil IPv6',
        'arch_l8_name' => 'כור ההיתוך של בראשית', 'arch_l8_desc' => 'חסינות טמפורלית ZFS · שילוב עצבי Brainflow · סריקה אקוסטית Fast Fourier',
        'arch_l7_name' => 'דבר האלוהים', 'arch_l7_desc' => 'תנ"ך AKJV (94 ספרים · 39,482 פסוקים) · 57 נבואות · 33 סיפורי גן עדן · אלבום שירי הודיה · חתום ב-SHA-256',
        'arch_l6_name' => 'מעטפת קול וסוכנים', 'arch_l6_desc' => 'זיהוי דיבור Whisper → רתמת Omahon (תאימות XML/JSON) → סינתזת קול Kokoro — הפעלה קולית רציפה',
        'arch_l5_name' => 'מחסנית בינה מלאכותית', 'arch_l5_desc' => '4 מודלי GGUF מתקדמים (haiku, sonnet, opus, opus-iq3) · סביבת הפיתוח Alfred IDE · דפדפן Alfred · מנוע Meilisearch',
        'arch_l4_name' => 'אבטחה וחותם Omahon', 'arch_l4_desc' => 'פרוטוקול Veil (הצפנת Kyber-1024) · הצפנת AES-256-GCM · חותם Omahon (הגנת אתחול, שומר, כספת, אימות) · 38 מודולי LSM',
        'arch_l3_name' => 'כלכלה ונחיל', 'arch_l3_desc' => 'מטבע GSM ברשת סולאנה · כרייה · מענקים · נחיל רשת Skynet · רשת חישוב מבוזרת',
        'arch_l2_name' => 'סביבת שולחן עבודה', 'arch_l2_desc' => 'סביבת XFCE 4.18 (מוקשחת) · מנהל תצוגה LightDM · ערכת נושא Arc Dark · סמלי Papirus · גופן JetBrains Mono',
        'arch_l1_name' => 'תשתית וליבה', 'arch_l1_desc' => 'בסיס דביאן Trixie (13) · ליבת לינוקס Mainline 7.0.12 · 1335 עוגני בנייה מאומתים · תמיכת x86_64 אוניברסלית',

        // ── INDEX.PHP COMPARE ──
        'comp_title' => 'Alfred מול כל השאר',
        'comp_sub' => 'כל מערכת הפעלה אחרת נבנתה לפני קיומה של הבינה המלאכותית. Alfred נבנתה משום שהבינה המלאכותית קיימת.',
        'comp_th1' => 'וקטור ריבוני', 'comp_th2' => 'macOS Sequoia', 'comp_th3' => 'Windows 11 / 12', 'comp_th4' => 'ChromeOS', 'comp_th5' => 'Alfred Linux 7.77 GA',
        'comp_r1' => 'מרכז AI לא מצונזר Genesis Forge', 'comp_r2' => 'רתמת סוכנים (Omahon)', 'comp_r3' => 'ממשק עצבי מבודד אקוסטית', 'comp_r4' => 'הצפנה פוסט-קוונטית', 'comp_r5' => 'אימות עוגני בנייה', 'comp_r6' => 'קונצנזוס PoW קריפטוגרפי (GSM)', 'comp_r7' => 'בית חכם מובנה', 'comp_r8' => 'שליטה בצי רובוטים', 'comp_r9' => 'להרוויח בזמן החישוב', 'comp_r10' => 'חותם קריפטוגרפי Omahon ואפס-אמון',
        'comp_r11' => 'ניתוב רשת Tor והסוואת Darknet', 'comp_r12' => 'תצלומי מצב בלתי ניתנים לשינוי של מסע בזמן ZFS',

        // ── INDEX.PHP ECONOMY ──
        'econ_title' => 'להרוויח בזמן החישוב',
        'econ_sub' => 'מטבע GSM פעיל ברשת סולאנה. כלכלה מבוססת עבודה — תרמו והרוויחו. אספקה של מיליארד מטבעות, חלוקת כרייה 80/20.',
        'econ_earn' => '⬆ להרוויח GSM', 'econ_spend' => '⬇ לבזבז GSM',

        // ── INDEX.PHP TECH STACK ──
        'tech_title' => 'מחסנית טכנולוגית',
        'tech_sub' => 'יסודות מוכחים. שילוב פורץ דרך.',

        // ── INDEX.PHP ROADMAP ──
        'rm_title' => 'נתיב הממלכה',
        'rm_sub' => 'נבנה באמונה. נמסר בספרינטים. מהאתחול הראשון ועד למספר האלוהי.',
        'rm_s0_t' => 'ספרינט 0 — תשתית הפרויקט', 'rm_s0_d' => 'מחקר, תכנון, ארכיטקטורה, תיעוד, פריסת צוות הסוכנים',
        'rm_s1_t' => 'ספרינט 1 — קובץ ISO אתחול + קול + ADE', 'rm_s1_d' => 'תמונת מערכת ראשונה עם סביבת שולחן העבודה Alfred והסייע הקולי',
        'rm_s15_t' => 'ספרינט 1.5 — קובץ ISO מלא v2.0', 'rm_s15_d' => 'דפדפן Alfred + סביבת פיתוח Alfred IDE + קול Kokoro + מנוע Meilisearch + מתקין Calamares.',
        'rm_s16_t' => 'ספרינט 1.6 — ביסוס מחדש על Trixie v3.0 + ליבה 6.12', 'rm_s16_d' => 'מבוסס על דביאן Trixie (13). ליבת 6.12 LTS. אתחול כפול UEFI/GRUB2.',
        'rm_s23_t' => 'ספרינט 2–3 — יישומון ברוכים הבאים v4.0 + קול 2.0 + חנות Alfred', 'rm_s23_d' => 'אשף התקנה ראשונית, מילת הפעלה "Hey Alfred", חנות היישומים Alfred Store (מרכז Flatpak), כלי העדכון alfred-update',
        'rm_s40_t' => 'ספרינט 4–7 — v7.77 GA מהדורת ממלכת האלוהים', 'rm_s40_d' => 'ליבת לינוקס Mainline 7.0.12. 1335 עוגני בנייה. 4 מודלי בינה מלאכותית GGUF. רתמת Omahon. תנ"ך AKJV מובנה. 27 שירי תהילים. חישוב GPU. אחסון ב-6 שכבות.',

        // ── INDEX.PHP ECOSYSTEM ──
        'eco_title' => 'חלק מהממלכה',
        'eco_sub' => 'Alfred Linux היא עמוד תווך אחד באקולוגיית GoSiteMe — תשעה עמודים הבונים את האינטרנט הריבוני לתהילת האלוהים.',

        // ── INDEX.PHP FOOTER ──
        'foot_p1' => 'מערכת ההפעלה הריבונית הראשונה בעולם המבוססת על בינה מלאכותית. חתומה בחותם Omahon &mdash; נשמת האלוהים, בלתי ניתנת להשחתה. מגיעה מובנית עם 4 מודלי בינה מלאכותית GGUF ו-1335 עוגני בנייה מאומתים. דבר האלוהים נשמר בסיליקון ובקוד. נבנה על ידי המפקד דני ויליאם פרז לתהילת האלוהים וממלכתו.',
        'foot_p2' => '&ldquo;יָבֵשׁ חָצִיר נָבֵל צִיץ וּדְבַר אֱלֹהֵינוּ יָקוּם לְעוֹלָם.&rdquo; &mdash; ישעיהו מ:ח',
        'foot_h_prod' => 'המוצר', 'foot_h_eco' => 'אקולוגיה', 'foot_h_comm' => 'קהילה',

        // FILESYSTEM BREAKDOWN
        'fs_title' => 'מנועי אלמוות הנתונים',
        'fs_sub' => 'מערכת Alfred Linux מספקת בדיוק את אותה ארכיטקטורת קבצים God-Tier למחשב נייד רגיל כמו לחוות שרתים של מיליארד דולר.',
        'fs_zfs_title' => 'ZFS (תקן ה-God-Tier)',
        'fs_zfs_desc' => 'לחוות שרתים ואלמוות נתונים. אם ביט משתבש עקב כשל חומרה, ZFS מרפא את הקובץ באופן מיידי באמצעות סכומי ביקורת. מפעיל את מנוע המסע בזמן המוחלט עבור מערכות עם 16GB+ RAM.',
        'fs_btrfs_title' => 'Btrfs (השומר הקל)',
        'fs_btrfs_desc' => 'למען העם. מיועד למחשבים ניידים או למערכות עם זיכרון RAM נמוך. הוא מספק בדיוק את אותה הגנת מסע בזמן ויכולות צילום מצב ללא עומס המטמון הכבד של ZFS.',
        'fs_xfs_title' => 'XFS / Ext4 (שדי מהירות ה-AI)',
        'fs_xfs_desc' => 'לתפוקה גולמית. כאשר צומת Skynet מבוזר צריך לטעון מודל רשת עצבית GGUF מסיבי ישירות ל-VRAM, מערכות קבצים אלו מספקות מהירות קריאה/כתיבה מוחלטת ללא פשרות.',
    ],
];
