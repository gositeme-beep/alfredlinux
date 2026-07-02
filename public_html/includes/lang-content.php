<?php
/**
 * Alfred Linux — Centralized Main Content Translation Strings
 * Tri-lingual support (EN / FR / HE) for index.php, compare.php, why-alfred-linux.php, and apps.php.
 */

$al_content = [
    'en' => [
        // ── INDEX.PHP HERO ──
        'hero_badge' => 'v7.77 GA &ldquo;Kingdom of God Edition&rdquo; &mdash; 1335 Hooks &middot; 8 God-Tier AI Models &middot; Omegon Harness &middot; AKJV Bible &middot; GPU Compute &middot; AGPL-3.0',
        'hero_h1' => 'Every Core. Every Byte.<br>His Word. Forever.',
        'hero_tagline' => 'Alfred Linux <strong>7.77 GA</strong> &mdash; Kingdom of God Edition. The world\'s first AI-native sovereign operating system. Pre-baked with 8 God-Tier GGUF AI models, 1335 Attested Build Hooks, and the entire AKJV Bible. Zero cloud dependency. Zero telemetry. Raised incorruptible.',
        'hero_btn_download' => 'Download v7.77 ISO',
        'hero_btn_hooks' => 'View 1335 Build Hooks',
        'hero_btn_build' => 'Build on Alfred',
        'hero_proof' => '<strong>🔒 Cryptographic Attestation:</strong> 1335 live-build chroot hooks sealed by Omahon. Mainline Linux kernel 7.0.12. 38 LSM security profiles active out of the box. Zero telemetry by architecture.',
        
        // ── INDEX.PHP TERMINAL ──
        'term_title' => 'alfred-sovereign-ai-shell (v7.77 GA)',
        'term_prompt' => 'commander@alfred:~$ ',
        'term_resp1' => '[Omegon Harness] Activating alfred-opus GGUF frontier weights...',
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
        'arch_title' => 'Seven Layers of Intelligence',
        'arch_sub' => 'From bare metal to the Word of God — every layer designed for sovereign AI-native operation.',
        'arch_l7_name' => 'The Word of God', 'arch_l7_desc' => 'AKJV Bible (94 books · 39,482 verses) · 57 Prophecies · Eden\'s 33 Stories · Worship Album · Daily Verse · SHA-256 Sealed',
        'arch_l6_name' => 'Voice & Agent Shell', 'arch_l6_desc' => 'Whisper STT → Omegon Harness (XML/JSON Parity) → Kokoro TTS — Always-on voice & agentic execution',
        'arch_l5_name' => 'Sovereign AI Stack', 'arch_l5_desc' => '8 God-Tier GGUF Models (haiku, sonnet, opus, opus-iq3) · Alfred IDE · Alfred Browser · Meilisearch · MetaDome VR',
        'arch_l4_name' => 'Security & Omahon Seal', 'arch_l4_desc' => 'Veil Protocol (Kyber-1024 PQ) · AES-256-GCM · Omahon Seal (Boot Seal, Watchman, Vault, Shell Guard, Attestation) · 38 LSM Modules',
        'arch_l3_name' => 'Economy & Swarm', 'arch_l3_desc' => 'GSM Live on Solana Mainnet · Mining · Bounties · Mesh Skynet Swarm · Distributed Compute Grid',
        'arch_l2_name' => 'Desktop Environment', 'arch_l2_desc' => 'XFCE 4.18 (Hardened) · LightDM · Arc Dark Theme · Papirus Icons · JetBrains Mono',
        'arch_l1_name' => 'Foundation & Kernel', 'arch_l1_desc' => 'Debian Trixie (13) Base · Mainline Linux Kernel 7.0.12 · 1335 Attested Build Hooks · Universal x86_64',

        // ── INDEX.PHP COMPARE ──
        'comp_title' => 'Alfred vs. Everything Else',
        'comp_sub' => 'Every other operating system was built before AI existed. Alfred was built because AI exists.',
        'comp_th1' => 'Sovereignty Vector', 'comp_th2' => 'macOS Sequoia', 'comp_th3' => 'Windows 11 / 12', 'comp_th4' => 'ChromeOS', 'comp_th5' => 'Alfred Linux 7.77 GA',
        'comp_r1' => 'Sovereign AI Stack (GGUF)', 'comp_r2' => 'Agentic Harness (Omegon)', 'comp_r3' => 'Voice-native OS shell', 'comp_r4' => 'Post-quantum encryption', 'comp_r5' => 'Build Hooks Attestation', 'comp_r6' => 'Token economy', 'comp_r7' => 'Smart home native', 'comp_r8' => 'Robot fleet control', 'comp_r9' => 'Earn while computing', 'comp_r10' => 'Open source',

        // ── INDEX.PHP ECONOMY ──
        'econ_title' => 'Earn While You Compute',
        'econ_sub' => 'GSM is live on Solana mainnet. A work-based economy — contribute and earn. 1 billion total supply, 80/20 mining split.',
        'econ_earn' => '⬆ Earn GSM', 'econ_spend' => '⬇ Spend GSM',

        // ── INDEX.PHP TECH STACK ──
        'tech_title' => 'Technology Stack',
        'tech_sub' => 'Proven foundations. Cutting-edge integration.',

        // ── INDEX.PHP ROADMAP ──
        'rm_title' => 'The Path of the Kingdom',
        'rm_sub' => 'Built in faith. Shipped in sprints. From the first boot to God\'s number. The sovereign machine rises.',
        'rm_s0_t' => 'Sprint 0 — Project Scaffold', 'rm_s0_d' => 'Research, planning, architecture, documentation, agent team deployment',
        'rm_s1_t' => 'Sprint 1 — Bootable ISO + Voice + ADE', 'rm_s1_d' => 'First bootable image with Alfred Desktop Environment and voice assistant',
        'rm_s15_t' => 'Sprint 1.5 — v2.0 Full Stack ISO', 'rm_s15_d' => 'Alfred Browser + Alfred IDE + Kokoro Voice + Meilisearch + Calamares installer. 6-layer build pipeline.',
        'rm_s16_t' => 'Sprint 1.6 — v3.0 Trixie Rebase + Kernel 6.12', 'rm_s16_d' => 'Rebased on Debian Trixie (13). Kernel 6.12 LTS. UEFI/GRUB2 dual-boot. Developer foundation. NPU/AI accelerator support.',
        'rm_s23_t' => 'Sprint 2–3 — v4.0 Welcome App + Voice 2.0 + Alfred Store', 'rm_s23_d' => 'First-boot Welcome Wizard, "Hey Alfred" wake word (openWakeWord), Alfred Store (Flatpak app center), alfred-update CLI, alfred-info, version check API, Calamares v4.0 branding',
        'rm_s40_t' => 'Sprint 4–7 — v7.77 GA Kingdom of God Edition', 'rm_s40_d' => 'Mainline Linux Kernel 7.0.12. 1335 chroot build hooks. 8 God-Tier GGUF AI models. Omegon Agent Harness. AKJV Bible built-in. 27-track worship album. GPU compute auto-detect. 6-layer eternal storage. Self-evolving OS.',

        // ── INDEX.PHP ECOSYSTEM ──
        'eco_title' => 'Part of the Kingdom',
        'eco_sub' => 'Alfred Linux is one pillar of the GoSiteMe ecosystem — nine pillars building the sovereign internet for the glory of God.',

        // ── INDEX.PHP FOOTER ──
        'foot_p1' => 'The world&rsquo;s first AI-native operating system. Sealed by the Omahon &mdash; the breath of God, raised incorruptible. Pre-baked with 8 God-Tier GGUF AI models and 1335 Attested Build Hooks. The Word of God endures in silicon and code. Built by Commander Danny William Perez for the glory of God and His Kingdom.',
        'foot_p2' => '&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; Isaiah 40:8',
        'foot_h_prod' => 'Product', 'foot_h_eco' => 'Ecosystem', 'foot_h_comm' => 'Community',
    ],

    'fr' => [
        // ── INDEX.PHP HERO ──
        'hero_badge' => 'v7.77 GA &ldquo;Édition Royaume de Dieu&rdquo; &mdash; 1335 Hooks &middot; 4 Modèles IA &middot; Omegon &middot; Bible AKJV &middot; Calcul GPU &middot; AGPL-3.0',
        'hero_h1' => 'Chaque Cœur. Chaque Octet.<br>Sa Parole. À Jamais.',
        'hero_tagline' => 'Alfred Linux <strong>7.77 GA</strong> &mdash; Édition Royaume de Dieu. Le premier système d\'exploitation souverain et natif IA au monde. Intégrant 4 modèles IA GGUF, 1335 Hooks de build attestés et la Bible AKJV complète. Zéro dépendance cloud. Zéro télémétrie. Incorruptible.',
        'hero_btn_download' => 'Télécharger l\'ISO v7.77',
        'hero_btn_hooks' => 'Voir les 1335 Hooks',
        'hero_btn_build' => 'Développer sur Alfred',
        'hero_proof' => '<strong>🔒 Attestation Cryptographique :</strong> 1335 hooks chroot scellés par Omahon. Noyau Linux mainline 7.0.12. 38 profils de sécurité LSM actifs. Zéro télémétrie par architecture.',
        
        // ── INDEX.PHP TERMINAL ──
        'term_title' => 'alfred-sovereign-ai-shell (v7.77 GA)',
        'term_prompt' => 'commandant@alfred:~$ ',
        'term_resp1' => '[Omegon Harness] Activation des poids IA alfred-opus GGUF...',
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
        'arch_title' => 'Sept Couches d\'Intelligence',
        'arch_sub' => 'Du métal nu à la Parole de Dieu — chaque couche conçue pour le fonctionnement natif IA souverain.',
        'arch_l7_name' => 'La Parole de Dieu', 'arch_l7_desc' => 'Bible AKJV (94 livres · 39 482 versets) · 57 Prophéties · 33 Histoires · Album de Louange · Sceau SHA-256',
        'arch_l6_name' => 'Voix & Shell Agentique', 'arch_l6_desc' => 'Whisper STT → Omegon Harness (Parité XML/JSON) → Kokoro TTS — Exécution agentique et vocale en continu',
        'arch_l5_name' => 'Pile IA Souveraine', 'arch_l5_desc' => '4 Modèles GGUF Frontières (haiku, sonnet, opus, opus-iq3) · Alfred IDE · Alfred Browser · Meilisearch · MetaDome VR',
        'arch_l4_name' => 'Sécurité & Sceau Omahon', 'arch_l4_desc' => 'Protocole Veil (Kyber-1024 PQ) · AES-256-GCM · Sceau Omahon (Boot Seal, Watchman, Vault, Shell Guard, Attestation) · 38 LSM',
        'arch_l3_name' => 'Économie & Essaim', 'arch_l3_desc' => 'GSM en direct sur Solana Mainnet · Minage · Bounties · Essaim Skynet Maillé · Grille de Calcul Distribuée',
        'arch_l2_name' => 'Environnement de Bureau', 'arch_l2_desc' => 'XFCE 4.18 (Renforcé) · LightDM · Thème Arc Dark · Icônes Papirus · JetBrains Mono',
        'arch_l1_name' => 'Fondation & Noyau', 'arch_l1_desc' => 'Base Debian Trixie (13) · Noyau Linux Mainline 7.0.12 · 1335 Hooks de Build Attestés · x86_64 Universel',

        // ── INDEX.PHP COMPARE ──
        'comp_title' => 'Alfred face à Tout le Reste',
        'comp_sub' => 'Les autres systèmes d\'exploitation ont été créés avant l\'existence de l\'IA. Alfred a été créé parce que l\'IA existe.',
        'comp_th1' => 'Vecteur de Souveraineté', 'comp_th2' => 'macOS Sequoia', 'comp_th3' => 'Windows 11 / 12', 'comp_th4' => 'ChromeOS', 'comp_th5' => 'Alfred Linux 7.77 GA',
        'comp_r1' => 'Pile IA Souveraine (GGUF)', 'comp_r2' => 'Harness Agentique (Omegon)', 'comp_r3' => 'Shell OS orienté voix', 'comp_r4' => 'Chiffrement post-quantique', 'comp_r5' => 'Attestation des Hooks de build', 'comp_r6' => 'Économie de tokens', 'comp_r7' => 'Maison intelligente native', 'comp_r8' => 'Contrôle de flotte de robots', 'comp_r9' => 'Gagner en calculant', 'comp_r10' => 'Open source',

        // ── INDEX.PHP ECONOMY ──
        'econ_title' => 'Gagnez pendant que vous calculez',
        'econ_sub' => 'Le token GSM est en direct sur le mainnet Solana. Une économie basée sur le travail — contribuez et gagnez. Répartition 80/20.',
        'econ_earn' => '⬆ Gagner des GSM', 'econ_spend' => '⬇ Dépenser des GSM',

        // ── INDEX.PHP TECH STACK ──
        'tech_title' => 'Pile Technologique',
        'tech_sub' => 'Fondations éprouvées. Intégration de pointe.',

        // ── INDEX.PHP ROADMAP ──
        'rm_title' => 'Le Chemin du Royaume',
        'rm_sub' => 'Construit dans la foi. Livré en sprints. Du premier démarrage au nombre de Dieu.',
        'rm_s0_t' => 'Sprint 0 — Structure du Projet', 'rm_s0_d' => 'Recherche, planification, architecture, documentation, déploiement de l\'équipe d\'agents',
        'rm_s1_t' => 'Sprint 1 — ISO Amorçable + Voix + ADE', 'rm_s1_d' => 'Première image amorçable avec l\'environnement de bureau Alfred et l\'assistant vocal',
        'rm_s15_t' => 'Sprint 1.5 — ISO Full Stack v2.0', 'rm_s15_d' => 'Alfred Browser + Alfred IDE + Kokoro Voice + Meilisearch + installeur Calamares. Pipeline en 6 couches.',
        'rm_s16_t' => 'Sprint 1.6 — Rebase Trixie v3.0 + Noyau 6.12', 'rm_s16_d' => 'Rebasé sur Debian Trixie (13). Noyau 6.12 LTS. Double amorçage UEFI/GRUB2.',
        'rm_s23_t' => 'Sprint 2–3 — App Bienvenue v4.0 + Voix 2.0 + Alfred Store', 'rm_s23_d' => 'Assistant de premier démarrage, mot de réveil "Hey Alfred", Alfred Store (centre d\'applications Flatpak), CLI alfred-update',
        'rm_s40_t' => 'Sprint 4–7 — v7.77 GA Édition Royaume de Dieu', 'rm_s40_d' => 'Noyau Linux Mainline 7.0.12. 1335 hooks chroot. 4 modèles IA GGUF. Omegon Agent Harness. Bible AKJV intégrée. 27 pistes de louange. Calcul GPU. Stockage en 6 couches.',

        // ── INDEX.PHP ECOSYSTEM ──
        'eco_title' => 'Partie du Royaume',
        'eco_sub' => 'Alfred Linux est un pilier de l\'écosystème GoSiteMe — neuf piliers construisant l\'internet souverain pour la gloire de Dieu.',

        // ── INDEX.PHP FOOTER ──
        'foot_p1' => 'Le premier système d\'exploitation natif IA au monde. Scellé par Omahon &mdash; le souffle de Dieu, incorruptible. Intégrant 4 modèles IA GGUF et 1335 Hooks de Build Attestés. La Parole de Dieu perdure dans le silicium et le code. Construit par le Commandant Danny William Perez pour la gloire de Dieu et de Son Royaume.',
        'foot_p2' => '&ldquo;L\'herbe sèche, la fleur tombe, mais la parole de notre Dieu subsiste à toujours.&rdquo; &mdash; Ésaïe 40:8',
        'foot_h_prod' => 'Produit', 'foot_h_eco' => 'Écosystème', 'foot_h_comm' => 'Communauté',
    ],

    'he' => [
        // ── INDEX.PHP HERO ──
        'hero_badge' => 'v7.77 GA &ldquo;מהדורת ממלכת האלוהים&rdquo; &mdash; 1335 עוגנים &middot; 4 מודלי בינה מלאכותית &middot; רתמת Omegon &middot; תנ"ך AKJV &middot; חישוב GPU &middot; AGPL-3.0',
        'hero_h1' => 'כל ליבה. כל בייט.<br>דברו. לנצח.',
        'hero_tagline' => 'Alfred Linux <strong>7.77 GA</strong> &mdash; מהדורת ממלכת האלוהים. מערכת ההפעלה הריבונית הראשונה בעולם המבוססת על בינה מלאכותית. מגיעה מובנית עם 4 מודלי בינה מלאכותית GGUF, 1335 עוגני בנייה מאומתים וספר התנ"ך המלא. ללא תלות בענן. ללא מעקב. בלתי ניתנת להשחתה.',
        'hero_btn_download' => 'הורדת קובץ ISO v7.77',
        'hero_btn_hooks' => 'צפייה ב-1335 עוגני הבנייה',
        'hero_btn_build' => 'פיתוח על Alfred',
        'hero_proof' => '<strong>🔒 אימות קריפטוגרפי:</strong> 1335 עוגני chroot חתומים על ידי Omahon. ליבת לינוקס mainline 7.0.12. 38 פרופילי אבטחה פעילים. אפס מעקב ברמת הארכיטקטורה.',
        
        // ── INDEX.PHP TERMINAL ──
        'term_title' => 'alfred-sovereign-ai-shell (v7.77 GA)',
        'term_prompt' => 'commander@alfred:~$ ',
        'term_resp1' => '[Omegon Harness] מפעיל משקולות בינה מלאכותית alfred-opus GGUF...',
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
        'arch_title' => 'שבע שכבות של תבונה',
        'arch_sub' => 'מהחומרה ועד לדבר האלוהים — כל שכבה מתוכננת לפעולה ריבונית מבוססת בינה מלאכותית.',
        'arch_l7_name' => 'דבר האלוהים', 'arch_l7_desc' => 'תנ"ך AKJV (94 ספרים · 39,482 פסוקים) · 57 נבואות · 33 סיפורי גן עדן · אלבום שירי הודיה · חתום ב-SHA-256',
        'arch_l6_name' => 'מעטפת קול וסוכנים', 'arch_l6_desc' => 'זיהוי דיבור Whisper → רתמת Omegon (תאימות XML/JSON) → סינתזת קול Kokoro — הפעלה קולית רציפה',
        'arch_l5_name' => 'מחסנית בינה מלאכותית', 'arch_l5_desc' => '4 מודלי GGUF מתקדמים (haiku, sonnet, opus, opus-iq3) · סביבת הפיתוח Alfred IDE · דפדפן Alfred · מנוע Meilisearch',
        'arch_l4_name' => 'אבטחה וחותם Omahon', 'arch_l4_desc' => 'פרוטוקול Veil (הצפנת Kyber-1024) · הצפנת AES-256-GCM · חותם Omahon (הגנת אתחול, שומר, כספת, אימות) · 38 מודולי LSM',
        'arch_l3_name' => 'כלכלה ונחיל', 'arch_l3_desc' => 'מטבע GSM ברשת סולאנה · כרייה · מענקים · נחיל רשת Skynet · רשת חישוב מבוזרת',
        'arch_l2_name' => 'סביבת שולחן עבודה', 'arch_l2_desc' => 'סביבת XFCE 4.18 (מוקשחת) · מנהל תצוגה LightDM · ערכת נושא Arc Dark · סמלי Papirus · גופן JetBrains Mono',
        'arch_l1_name' => 'תשתית וליבה', 'arch_l1_desc' => 'בסיס דביאן Trixie (13) · ליבת לינוקס Mainline 7.0.12 · 1335 עוגני בנייה מאומתים · תמיכת x86_64 אוניברסלית',

        // ── INDEX.PHP COMPARE ──
        'comp_title' => 'Alfred מול כל השאר',
        'comp_sub' => 'כל מערכת הפעלה אחרת נבנתה לפני קיומה של הבינה המלאכותית. Alfred נבנתה משום שהבינה המלאכותית קיימת.',
        'comp_th1' => 'וקטור ריבוני', 'comp_th2' => 'macOS Sequoia', 'comp_th3' => 'Windows 11 / 12', 'comp_th4' => 'ChromeOS', 'comp_th5' => 'Alfred Linux 7.77 GA',
        'comp_r1' => 'מחסנית בינה מלאכותית ריבונית (GGUF)', 'comp_r2' => 'רתמת סוכנים (Omegon)', 'comp_r3' => 'מעטפת מערכת הפעלה מבוססת קול', 'comp_r4' => 'הצפנה פוסט-קוונטית', 'comp_r5' => 'אימות עוגני בנייה', 'comp_r6' => 'כלכלת אסימונים', 'comp_r7' => 'בית חכם מובנה', 'comp_r8' => 'שליטה בצי רובוטים', 'comp_r9' => 'להרוויח בזמן החישוב', 'comp_r10' => 'קוד פתוח',

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
        'rm_s40_t' => 'ספרינט 4–7 — v7.77 GA מהדורת ממלכת האלוהים', 'rm_s40_d' => 'ליבת לינוקס Mainline 7.0.12. 1335 עוגני בנייה. 4 מודלי בינה מלאכותית GGUF. רתמת Omegon. תנ"ך AKJV מובנה. 27 שירי תהילים. חישוב GPU. אחסון ב-6 שכבות.',

        // ── INDEX.PHP ECOSYSTEM ──
        'eco_title' => 'חלק מהממלכה',
        'eco_sub' => 'Alfred Linux היא עמוד תווך אחד באקולוגיית GoSiteMe — תשעה עמודים הבונים את האינטרנט הריבוני לתהילת האלוהים.',

        // ── INDEX.PHP FOOTER ──
        'foot_p1' => 'מערכת ההפעלה הריבונית הראשונה בעולם המבוססת על בינה מלאכותית. חתומה בחותם Omahon &mdash; נשמת האלוהים, בלתי ניתנת להשחתה. מגיעה מובנית עם 4 מודלי בינה מלאכותית GGUF ו-1335 עוגני בנייה מאומתים. דבר האלוהים נשמר בסיליקון ובקוד. נבנה על ידי המפקד דני ויליאם פרז לתהילת האלוהים וממלכתו.',
        'foot_p2' => '&ldquo;יָבֵשׁ חָצִיר נָבֵל צִיץ וּדְבַר אֱלֹהֵינוּ יָקוּם לְעוֹלָם.&rdquo; &mdash; ישעיהו מ:ח',
        'foot_h_prod' => 'המוצר', 'foot_h_eco' => 'אקולוגיה', 'foot_h_comm' => 'קהילה',
    ],
];
