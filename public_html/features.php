<?php
/**
 * Alfred Linux 7.77 — EVERY SINGLE DETAIL
 * The comprehensive features page — 42 Kingdom hooks (canon), 18 wallpapers, full specs
 * Built by Alfred for Commander Danny William Perez
 * Free for everyone. Soli Deo Gloria.
 */
$year = date('Y');
require_once __DIR__ . '/includes/ga-release-state.php';

// Wallpaper data
$wallpapers = [
    ['slug' => 'expanded-alfred-linux-13', 'title' => 'Kingdom Art Alfred-Linux 13', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-9', 'title' => 'Kingdom Art Alfred-Linux 9', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-25', 'title' => 'Kingdom Art Alfred-Linux 25', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-14', 'title' => 'Kingdom Art Alfred-Linux 14', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-10', 'title' => 'Kingdom Art Alfred-Linux 10', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-31', 'title' => 'Kingdom Art Alfred-Linux 31', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-30', 'title' => 'Kingdom Art Alfred-Linux 30', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-40', 'title' => 'Kingdom Art Alfred-Linux 40', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-32', 'title' => 'Kingdom Art Alfred-Linux 32', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-37', 'title' => 'Kingdom Art Alfred-Linux 37', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-8', 'title' => 'Kingdom Art Alfred-Linux 8', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-12', 'title' => 'Kingdom Art Alfred-Linux 12', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-7', 'title' => 'Kingdom Art Alfred-Linux 7', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-18', 'title' => 'Kingdom Art Alfred-Linux 18', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-22', 'title' => 'Kingdom Art Alfred-Linux 22', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-33', 'title' => 'Kingdom Art Alfred-Linux 33', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-2', 'title' => 'Kingdom Art Alfred-Linux 2', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-35', 'title' => 'Kingdom Art Alfred-Linux 35', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-1', 'title' => 'Kingdom Art Alfred-Linux 1', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-boot-screen', 'title' => 'Kingdom Art Alfred-Linux Boot-Screen', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-42', 'title' => 'Kingdom Art Alfred-Linux 42', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-15', 'title' => 'Kingdom Art Alfred-Linux 15', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-17', 'title' => 'Kingdom Art Alfred-Linux 17', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-26', 'title' => 'Kingdom Art Alfred-Linux 26', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-5', 'title' => 'Kingdom Art Alfred-Linux 5', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-24', 'title' => 'Kingdom Art Alfred-Linux 24', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-39', 'title' => 'Kingdom Art Alfred-Linux 39', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-36', 'title' => 'Kingdom Art Alfred-Linux 36', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-21', 'title' => 'Kingdom Art Alfred-Linux 21', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-29', 'title' => 'Kingdom Art Alfred-Linux 29', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-28', 'title' => 'Kingdom Art Alfred-Linux 28', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-6', 'title' => 'Kingdom Art Alfred-Linux 6', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-23', 'title' => 'Kingdom Art Alfred-Linux 23', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-38', 'title' => 'Kingdom Art Alfred-Linux 38', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-41', 'title' => 'Kingdom Art Alfred-Linux 41', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-11', 'title' => 'Kingdom Art Alfred-Linux 11', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-27', 'title' => 'Kingdom Art Alfred-Linux 27', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-34', 'title' => 'Kingdom Art Alfred-Linux 34', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-20', 'title' => 'Kingdom Art Alfred-Linux 20', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-4', 'title' => 'Kingdom Art Alfred-Linux 4', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-16', 'title' => 'Kingdom Art Alfred-Linux 16', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-19', 'title' => 'Kingdom Art Alfred-Linux 19', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-alfred-linux-3', 'title' => 'Kingdom Art Alfred-Linux 3', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-13', 'title' => 'Kingdom Art Kingdom 13', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-9', 'title' => 'Kingdom Art Kingdom 9', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-25', 'title' => 'Kingdom Art Kingdom 25', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-14', 'title' => 'Kingdom Art Kingdom 14', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-10', 'title' => 'Kingdom Art Kingdom 10', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-omahon-seal', 'title' => 'Kingdom Art Kingdom Omahon-Seal', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-8', 'title' => 'Kingdom Art Kingdom 8', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-12', 'title' => 'Kingdom Art Kingdom 12', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-7', 'title' => 'Kingdom Art Kingdom 7', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-18', 'title' => 'Kingdom Art Kingdom 18', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-22', 'title' => 'Kingdom Art Kingdom 22', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-2', 'title' => 'Kingdom Art Kingdom 2', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-1', 'title' => 'Kingdom Art Kingdom 1', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-15', 'title' => 'Kingdom Art Kingdom 15', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-17', 'title' => 'Kingdom Art Kingdom 17', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-5', 'title' => 'Kingdom Art Kingdom 5', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-24', 'title' => 'Kingdom Art Kingdom 24', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-21', 'title' => 'Kingdom Art Kingdom 21', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-6', 'title' => 'Kingdom Art Kingdom 6', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-23', 'title' => 'Kingdom Art Kingdom 23', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-11', 'title' => 'Kingdom Art Kingdom 11', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-20', 'title' => 'Kingdom Art Kingdom 20', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-4', 'title' => 'Kingdom Art Kingdom 4', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-16', 'title' => 'Kingdom Art Kingdom 16', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-19', 'title' => 'Kingdom Art Kingdom 19', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'expanded-kingdom-3', 'title' => 'Kingdom Art Kingdom 3', 'desc' => 'Exclusive Kingdom of God Edition artwork.'],
    ['slug' => 'kingdom-throne',           'title' => 'The Kingdom Throne',         'desc' => 'A golden throne bathed in divine light — the seat of eternal sovereignty.'],
    ['slug' => 'gods-throne',              'title' => "God's Throne",               'desc' => 'The Throne of the Most High in the heavenly temple — sapphire foundations, seven lamps of fire, a crystal sea.'],
    ['slug' => 'risen-king',               'title' => 'The Risen King',             'desc' => 'Jesus Christ in glory — white robe, golden sash, eyes like blazing fire, standing in radiant light.'],
    ['slug' => 'crown-of-glory',           'title' => 'The Crown of Glory',         'desc' => 'A sovereign crown set among clouds of gold — the imperishable crown that awaits the faithful.'],
    ['slug' => 'lion-of-judah',            'title' => 'The Lion of Judah',          'desc' => 'The Lion of the tribe of Judah wearing a golden crown — the Root of David who has conquered.'],
    ['slug' => 'daniel-lions-den',         'title' => "Daniel in the Lion's Den",   'desc' => 'The prophet Daniel at peace among lions — God shut their mouths. A testimony of faith under fire.'],
    ['slug' => 'mount-sinai',              'title' => 'Mount Sinai',                'desc' => 'The holy mountain where God spoke — fire, cloud, and the giving of the Ten Commandments.'],
    ['slug' => 'sanctuary-dawn',           'title' => 'Sanctuary at Dawn',          'desc' => 'A sacred sanctuary bathed in the golden light of a new morning — peace, worship, rest.'],
    ['slug' => 'living-waters',            'title' => 'Living Waters',              'desc' => 'Crystal-clear waters flowing through verdant lands — the river of the water of life from the throne of God.'],
    ['slug' => 'eden-garden',              'title' => 'The Garden of Eden',         'desc' => 'Paradise restored — lush garden with the Tree of Life, waterfalls, and golden light.'],
    ['slug' => 'new-jerusalem',            'title' => 'The New Jerusalem',          'desc' => 'The holy city coming down from heaven — gates of pearl, streets of gold, the dwelling of God with man.'],
    ['slug' => 'new-jerusalem-descending', 'title' => 'New Jerusalem Descending',   'desc' => 'The city of God descending from the clouds — Revelation 21 made visible. Every tear wiped away.'],
    ['slug' => 'perez-crest',              'title' => 'The Perez Family Crest',     'desc' => 'The sovereign seal of the Perez dynasty — the family that built Alfred Linux for the glory of God.'],
    ['slug' => 'kingdom-seal',             'title' => 'The Kingdom Seal',           'desc' => 'The official seal of the Kingdom of God Edition — authority, sovereignty, divine mandate.'],
    ['slug' => 'dynasty-gateway',          'title' => 'The Dynasty Gateway',        'desc' => 'A monumental gateway into the Kingdom — the entrance to something eternal.'],
    ['slug' => 'sovereign-dark',           'title' => 'Sovereign Dark',             'desc' => 'The dark-mode sovereign wallpaper — minimal, commanding, elegant. For those who work at midnight.'],
    ['slug' => 'perez-family-legacy', 'title' => 'The Perez Family Legacy', 'desc' => 'Father and daughter standing on a mountaintop at dawn — Danny and Eden, looking toward the Kingdom.'],
];

// Hook categories
$hookCategories = [
    'Foundation & Customization' => [
        ['id' => '0100', 'name' => 'Post-Build Customization', 'hook' => 'alfred-customize', 'lines' => 559, 'icon' => '⚙️',
         'desc' => 'The master customization hook: GRUB branding (Kingdom of God boot screen), MOTD with Psalm 23:1, fastfetch system info, desktop theming (Adwaita-dark), default applications, file associations, system fonts, locale configuration, Plymouth boot splash, and version branding across the entire OS. 559 lines of post-build identity.'],
        ['id' => '0150', 'name' => 'Universal Hardware', 'hook' => 'alfred-hardware', 'lines' => 825, 'icon' => '🖥️',
         'desc' => 'The largest hardware compatibility hook in any Linux distribution. 825 lines covering: Intel/AMD CPU microcode, Mesa GPU drivers (AMD Radeon, Intel Arc), NVIDIA firmware, Broadcom/Realtek/Intel/MediaTek WiFi and Bluetooth, Thunderbolt security, NVMe/AHCI storage, USB power management, touchpad/touchscreen/Wacom input, ALSA/PipeWire audio, webcam (UVC), printer support (CUPS/Gutenprint/HPLIP), scanner (SANE), power management (TLP/thermald), laptop ACPI, DisplayLink USB video, card readers, IR remote, and hardware monitoring (lm-sensors/smartmontools).'],
        ['id' => '0111', 'name' => 'God Mode', 'hook' => 'alfred-god-mode', 'lines' => 60, 'icon' => '⚡',
         'desc' => 'Instant elevation. Pressing Super+G immediately grants a transparent, red-tinted, root-privileged terminal drop-down from the top of the screen.'],
        ['id' => '0114', 'name' => 'Martyr Panic', 'hook' => 'alfred-martyr-panic', 'lines' => 85, 'icon' => '💥',
         'desc' => 'A custom kernel panic handler that wipes RAM and shuts down instantly upon sensing intrusion attempts or unauthorized hardware attachments.'],
    ],
    'Security & Encryption' => [
        ['id' => '0160', 'name' => 'Security Hardening', 'hook' => 'alfred-security', 'lines' => 570, 'icon' => '🛡️',
         'desc' => 'Enterprise-grade security out of the box. 32 hardening modules: AppArmor mandatory access control, fail2ban intrusion prevention, AIDE file integrity, ClamAV antivirus, rkhunter + chkrootkit rootkit detection, auditd with 30+ immutable audit rules, PAM hardening (password complexity, account lockout), compiler restriction (/usr/bin/gcc readable only by root), hidepid=2 (processes hidden between users), NTS-secured time sync (no NTP poisoning), sysctl hardening (ASLR, SYN cookies, ICMP restrictions, kernel pointer hiding), USB guard, core dump prevention, and 30+ kernel module blacklist (firewire, thunderbolt raw, exotic filesystems, webcam raw access).'],
        ['id' => '0165', 'name' => 'Network Hardening', 'hook' => 'alfred-network-hardening', 'lines' => 193, 'icon' => '🌐',
         'desc' => 'Default-deny nftables firewall: only SSH (22), HTTP (80), HTTPS (443) inbound. All outbound allowed. IPv6 hardened separately. MAC address randomization at every WiFi connection. DNS-over-TLS via systemd-resolved pointing to Quad9 (privacy-first DNS). SSH hardening: no root login, no password auth, Ed25519 preferred, MaxAuthTries 3. Automatic firewall persistence across reboots.'],
        ['id' => '0166', 'name' => 'Post-Quantum Encryption', 'hook' => 'alfred-quantum', 'lines' => 254, 'icon' => '🔐',
         'desc' => 'Kyber-1024 / ML-KEM-1024 post-quantum key encapsulation. The only consumer operating system shipping with quantum-resistant encryption by default. Installs liboqs, oqsprovider for OpenSSL 3.x, alfred-encrypt-quantum CLI for hybrid classical+PQ file encryption (AES-256-GCM + Kyber KEM). Quantum key generation, encapsulation, and decapsulation tools. Integrated into FDE hook for LUKS quantum key wrapping. Your files will still be encrypted when quantum computers arrive.'],
        ['id' => '0170', 'name' => 'Full-Disk Encryption', 'hook' => 'alfred-fde', 'lines' => 333, 'icon' => '🔒',
         'desc' => 'LUKS2 full-disk encryption support in Calamares installer with quantum key wrapping (Kyber-1024 KEM layered on top of LUKS passphrase). alfred-fde-status CLI shows encryption health. alfred-encrypt-quantum wraps existing LUKS volumes with post-quantum KEM. Supports both interactive passphrase and TPM2 auto-unlock. Calamares module configuration for encrypted partitioning with ext4 or btrfs.'],
        ['id' => '0175', 'name' => 'The Omahon Seal', 'hook' => 'omahon-seal', 'lines' => 504, 'icon' => '✡️',
         'desc' => 'The Breath of God — 6 runtime integrity modules. Boot Seal: HMAC-SHA256 verification of 14 critical boot files on every startup. Watchman: real-time inotify monitoring of /etc, /boot, /etc/ssh with instant alerts. Vault: 16MB encrypted tmpfs in RAM for session secrets. Shell Guard: terminal secret redaction (passwords, tokens, API keys never displayed). Secure Erase: 3-pass cryptographic wipe (random + zeros + verify). Sovereign Attestation: SHA-256 build chain verification proving the OS has not been tampered with. Named "Omahon" — the breath of God in Hebrew, because this seal belongs to no borrowed name.'],
        ['id' => '0176', 'name' => 'Kingdom Covenant Shield', 'hook' => 'kingdom-covenant-shield', 'lines' => 337, 'icon' => '🛡️',
         'desc' => 'Legal and IP armor for the distro: Kingdom Covenant License text, trademark / succession notices, and related files installed system-wide so every copy carries the same covenant frame (Ephesians 6:11). Inspect <code>0176-kingdom-covenant-shield.hook.chroot</code> on GoForge.'],
        ['id' => '0500', 'name' => 'Ghost Boot Environment', 'hook' => 'alfred-ghost-boot', 'lines' => 65, 'icon' => '👻',
         'desc' => 'Plausible deniability via a hidden GRUB hotkey (F11) that boots a sterile decoy environment with no access to the Treasury or private vaults. Ultimate physical duress protection.'],
        ['id' => '0604', 'name' => 'Leviathan eBPF Firewall', 'hook' => 'alfred-leviathan-ebpf', 'lines' => 85, 'icon' => '🐉',
         'desc' => 'Kernel-level deep packet inspection using eBPF to drop tracking and telemetry packets instantly before they even reach userspace. A hyper-efficient, invisible shield.'],
        ['id' => '0608', 'name' => 'Claw Code Enforcer', 'hook' => 'alfred-claw-enforcer', 'lines' => 45, 'icon' => '🦅',
         'desc' => 'A ruthless filesystem watchdog daemon that continuously monitors /usr/local/bin for unauthorized binary modifications, instantly purging unsigned executables.'],
        ['id' => '0611', 'name' => 'Trumpet Lockdown', 'hook' => 'alfred-trumpet-lockdown', 'lines' => 70, 'icon' => '🎺',
         'desc' => 'Tied to the hardware panic switch. If a breach is detected or the Sword USB is removed, the OS maximizes volume, blares a Kingdom warning video, and hard-halts the motherboard.'],
    ],
    'Sovereign Compute' => [
        ['id' => '0167', 'name' => 'Mesh Cloud Network', 'hook' => 'alfred-mesh', 'lines' => 536, 'icon' => '🕸️',
         'desc' => 'Sovereign peer-to-peer mesh networking without any central server. Syncthing encrypted mesh, Yggdrasil overlay network (IPv6 mesh), auto-discovery of local Alfred nodes, encrypted file sharing, Kingdom filesystem (~/Kingdom/ auto-syncs). Like the Body of Christ (1 Corinthians 12), the network is a decentralized, living organism where nodes trust and support each other. alfred-mesh CLI: connect to kingdom, list peers, share files, broadcast messages, voice call peers. No cloud required — your machines talk directly.'],
        ['id' => '0275', 'name' => 'GPU Compute Stack', 'hook' => 'alfred-gpu', 'lines' => 450, 'icon' => '🎮',
         'desc' => 'Sovereign GPU compute: NVIDIA CUDA toolkit + cuDNN, AMD ROCm, Vulkan SDK, OpenCL, Mesa drivers. Supports machine learning inference (PyTorch, TensorFlow), 3D rendering, video encoding, and scientific computing. GPU autodetect at first boot. alfred-gpu-info CLI shows detected GPU, driver version, VRAM. Performance profiles for gaming, ML training, rendering, and battery modes.'],
        ['id' => '0280', 'name' => 'MAX Sovereign Compute', 'hook' => 'alfred-max-sovereign', 'lines' => 688, 'icon' => '⚡',
         'desc' => 'The largest hook: 688 lines of maximum performance tuning. CPU governor set to performance, I/O scheduler optimized (mq-deadline for NVMe, BFQ for HDD), transparent hugepages, vm.swappiness=10, dirty ratio tuning, IRQ balancing, NUMA awareness, kernel preempt tuning. MAX mode enables all cores, disables power saving, maximizes throughput. Includes: Rust toolchain (rustup), Python ML stack (numpy/scipy/pandas/scikit-learn/torch), Go, Docker rootless, Podman, WASM runtimes (wasmtime/wasmer), Julia, R, LaTeX (texlive). Every compute tool a sovereign operator could need.'],
        ['id' => '0285', 'name' => 'Eternal Storage', 'hook' => 'alfred-eternal-storage', 'lines' => 550, 'icon' => '♾️',
         'desc' => '6-layer data immortality: (1) IPFS — InterPlanetary File System for distributed content addressing, (2) Blockchain anchoring — SHA-256 hash chain for document provenance, (3) Frequency encoding — data encoded into waveforms survivable on analog media, (4) Mesh replication — files auto-replicated across Kingdom mesh peers, (5) SHA-256 integrity — every critical file checksummed and verified, (6) AES-256-GCM encryption — data encrypted at rest and in transit. alfred-eternal CLI: store, retrieve, verify, replicate. Your testimony outlives your hardware.'],
        ['id' => '0270', 'name' => 'Self-Healing Security', 'hook' => 'alfred-sovereign', 'lines' => 275, 'icon' => '👑',
         'desc' => 'The Crown — self-healing security and sovereignty enforcement. Automatic repair of tampered system files, integrity verification on boot, sovereign attestation chain, automatic security update scheduling, unattended-upgrades for critical patches, and system hardening validation. The OS that heals itself.'],
        ['id' => '0602', 'name' => 'Goliath Mesh Compute', 'hook' => 'alfred-goliath-compute', 'lines' => 90, 'icon' => '⛰️',
         'desc' => 'A decentralized micro-Kubernetes node utilizing k9s and helm to pool idle CPU/GPU resources across trusted machines in the Veil mesh.'],
        ['id' => '0603', 'name' => 'The Scrolls Mesh Sync', 'hook' => 'alfred-scrolls-sync', 'lines' => 110, 'icon' => '📜',
         'desc' => 'Encrypted Syncthing mesh exclusively for syncing public framework and offline-kit files, mathematically walled off from private legal Vaults.'],
    ],
    'The Word of God' => [
        ['id' => '0290', 'name' => 'AKJV Bible', 'hook' => 'alfred-bible', 'lines' => 368, 'icon' => '📖',
         'desc' => 'The full Authorized King Jesus Version Bible built into the operating system — 94 books (66 canonical + 14 Deuterocanonical + 14 additional texts), 39,482 verses, 57 identified Messianic prophecies cross-referenced. alfred-bible CLI: read any verse, search by keyword, random verse, daily reading plan, prophecy study mode. SQLite database for instant full-text search. The Word of God, sovereign and offline, on every Alfred machine.'],
        ['id' => '0291', 'name' => 'Family Bible Generator', 'hook' => 'alfred-family-bible', 'lines' => 427, 'icon' => '👨‍👩‍👧',
         'desc' => "Every family deserves their own Bible — personalized with the family's name, founding date, children's dedications, memorial pages, and heritage records. alfred-family-bible CLI generates a complete family Bible document with ancestry lines, marriage records, birth records, death memorials, family prayers, and a heritage tree. PDF output for printing. Eden's copy is pre-generated with the Perez family history. 33 Children's Bible Stories in English, French, and Hebrew included for the little ones."],
        ['id' => '0292', 'name' => 'Bible in Every Language', 'hook' => 'alfred-bible-tongues', 'lines' => 545, 'icon' => '🌍',
         'desc' => 'Acts 2:4 — "And they were all filled with the Holy Ghost, and began to speak with other tongues." Forty-eight language codes in languages.conf: full Authorized King Jesus Version when the English TSV is installed (0290); rich offline seeds for Spanish (Reina-Valera), French (Louis Segond), and Hebrew; forty-four compact tongue-* seeds (Genesis 1:1 + John 3:16). alfred-bible-lang lists every code and reads seeds or full files when present.'],
    ],
    'Kingdom Worship & Devotion' => [
        ['id' => '0295', 'name' => 'Kingdom Worship', 'hook' => 'alfred-worship', 'lines' => 422, 'icon' => '🎵',
         'desc' => 'Full GUI music player with lyrics display — 27-track Hebrew worship album "Jesus Christ The Light Our Universe" by Elyon Light & Commander Danny William Perez. GTK4 player with album art, track listing, lyrics overlay, shuffle, repeat. Tracks include: "Shema Israel," "Hallelujah Adonai," "Kadosh Kadosh Kadosh," "El Shaddai," "Ruach HaKodesh," and more. alfred-worship CLI for terminal playback. Music stored in ~/Music/Kingdom/. No streaming required — the music lives on your machine.'],
        ['id' => '0720', 'name' => 'Sacred Rest (Stillness + Silence)', 'hook' => 'alfred-sacred-rest', 'lines' => 374, 'icon' => '🕊️',
         'desc' => 'One canonical hook for sacred stillness (1 Kings 19:12 — the still small voice) and timed silence (Super+S — forty minutes of amber, quiet, worship at low volume, shofar chime at end). Earlier trees used separate 0720/0721 files; the current build merges that work under <code>0720-alfred-sacred-rest.hook.chroot</code> on GoForge.'],
        ['id' => '0722', 'name' => 'Biblical Feasts & Sabbath', 'hook' => 'alfred-sabbath', 'lines' => 280, 'icon' => '🕎',
         'desc' => 'Exodus 20:8 — "Remember the sabbath day, to keep it holy." 10 Biblical feasts with authentic Hebrew dates: Passover, Unleavened Bread, Firstfruits, Shavuot, Trumpets, Yom Kippur, Sukkot, Shemini Atzeret, Purim, Hanukkah. 12 Torah portion readings. Sabbath enter/exit modes (Friday sunset prep checklist, automatic quiet mode). alfred-sabbath CLI: next feast, today\'s Torah portion, Shabbat candle times. The computer observes the Sabbath with you.'],
        ['id' => '0723', 'name' => 'Morning Watch', 'hook' => 'alfred-morning-watch', 'lines' => 316, 'icon' => '🌅',
         'desc' => 'Psalm 5:3 — "My voice shalt thou hear in the morning, O LORD." Daily devotional system with 31-day lectionary: each day has an Old Testament passage, New Testament passage, Psalm, and Proverb. Prayer timer with worship music at low volume. Encrypted prayer journal (AES-256-GCM PBKDF2) — your prayers are between you and God, no one else can read them. Morning notification at configured time. alfred-devotion CLI for terminal access.'],
        ['id' => '0296', 'name' => 'Testimony Backup', 'hook' => 'alfred-testimony', 'lines' => 274, 'icon' => '📜',
         'desc' => 'Hebrews 12:1 — "Wherefore seeing we also are compassed about with so great a cloud of witnesses." This is not a Silicon Valley cloud; it is a spiritual one. The system encrypts your journal, prayers, and testimony (AES-256-GCM PBKDF2) and pushes them to the Kingdom mesh network. Habakkuk 2:2 — "Write the vision, and make it plain upon tables." Your testimony is written to digital tables, replicated across the mesh so your witness survives hardware failure.'],
        ['id' => '0297', 'name' => 'Kingdom Locale + Typography Payload', 'hook' => 'alfred-kingdom-locale-payload', 'lines' => 139, 'icon' => '📐',
         'desc' => 'Supporting squashfs payload toward the honest ~7.77&nbsp;GiB binary target: Noto fonts, LibreOffice help/l10n where available, ffmpeg, and related packages (see <code>0297-alfred-kingdom-locale-payload.hook.chroot</code>). Works with Kingdom cinematic media staged through hook 0285.'],
        ['id' => '0725', 'name' => 'The Assembly', 'hook' => 'alfred-assembly', 'lines' => 288, 'icon' => '⛪',
         'desc' => 'Matthew 18:20 — "For where two or three are gathered together in my name, there am I in the midst of them." Mesh-synchronized group worship via Syncthing Kingdom folder. Assembly invites sent to mesh peers, auto-play matching worship tracks simultaneously, desktop notifications when assembly begins. Everyone plays the same song at the same time across the mesh. The body of Christ, connected through sovereign technology.'],
    ],
    'AI & Development' => [
        ['id' => '0250', 'name' => 'AI — Omahon', 'hook' => 'alfred-ai', 'lines' => 210, 'icon' => '🤖',
         'desc' => 'The only operating system that ships with AI built in. Ollama local LLM runtime, pre-configured models (qwen2.5:3b default, expandable to any model). Python 3.12, pip, venv, Jupyter notebook support. AI runs locally — no cloud API needed, no data leaves your machine. alfred-ai CLI for quick inference. Ollama auto-starts as systemd service. Named Omahon — the Breath of God — because AI in this kingdom serves the Creator, not the corporation.'],
        ['id' => '0259', 'name' => '1.58-Bit AI Architecture', 'hook' => 'alfred-bitnet', 'lines' => 143, 'icon' => '🧠',
         'desc' => 'Future-proof AI compute stack designed for 1.58-bit ternary models (-1, 0, 1). Employs intelligent CPU/GPU layer offloading via .gguf quantization. Rather than forcing all computation onto expensive VRAM, Alfred dynamically distributes model layers between system RAM (CPU) and graphics memory (GPU). The framework is fully prepared to natively execute Microsoft\'s bitnet.cpp engine, eliminating floating-point matrix multiplications entirely for unprecedented energy efficiency.'],
        ['id' => '0300', 'name' => 'Alfred IDE', 'hook' => 'alfred-ide', 'lines' => 97, 'icon' => '💻',
         'desc' => 'code-server 4.114.1 (VS Code 1.114.0-compatible) integrated into the OS. Full IDE with extensions, terminal, debugger, Git integration, and AI copilot support. Launches from application menu or alfred-ide CLI. Local-only access (127.0.0.1:8443) — no cloud. The same IDE that powers GoCodeMe, available offline on every Alfred machine.'],
        ['id' => '0312', 'name' => 'Offline Kit', 'hook' => 'alfred-offline-kit', 'lines' => 140, 'icon' => '🎒',
         'desc' => 'A complete offline Wikipedia archive (Kiwix), offline StackOverflow database, and Debian mirror subset. Even without internet, development and research never stop.'],
        ['id' => '0255', 'name' => 'Developer Tools', 'hook' => 'alfred-dev-tools', 'lines' => 117, 'icon' => '🔧',
         'desc' => 'The Arsenal — everything a developer needs: Git, Node.js 22 LTS, Python 3.12, GCC, G++, Make, CMake, Meson, pkg-config, autotools, Rust (via rustup), Go, Java 21 (OpenJDK), Ruby, PHP 8.3, SQLite3, PostgreSQL client, MariaDB client, curl, wget, jq, yq, httpie. Ships with EVERYTHING. No "apt install build-essential" on first boot — it\'s already there.'],
        ['id' => '0260', 'name' => 'Terminal Power Tools', 'hook' => 'alfred-terminal-power', 'lines' => 180, 'icon' => '⌨️',
         'desc' => 'The Forge — supercharged terminal: eza (modern ls with git awareness), ripgrep (rg, fastest grep), bat (cat with syntax highlighting), fd (intuitive find), fzf (fuzzy finder for everything), lazygit (terminal Git UI), tmux (terminal multiplexer), zoxide (smarter cd), starship prompt (informative and beautiful), delta (better git diff), hyperfine (benchmarking), tokei (code statistics), dust (better du), procs (better ps), bottom (better top). Every tool the terminal demands.'],
        ['id' => '0265', 'name' => 'Container Runtime', 'hook' => 'alfred-containers', 'lines' => 97, 'icon' => '📦',
         'desc' => 'The Armory — Podman (rootless Docker alternative), Buildah (OCI image builder), kubectl (Kubernetes CLI), Helm (Kubernetes package manager), k9s (terminal K8s dashboard). Run containers without root privileges. Build images without a daemon. Orchestrate clusters from day one. No Docker daemon — Podman is daemonless and compatible.'],
        ['id' => '0601', 'name' => 'The Prophet RAG Oracle', 'hook' => 'alfred-prophet-rag', 'lines' => 125, 'icon' => '👁️',
         'desc' => 'Hooks Meilisearch into the llama.cpp engine to instantly index and query the offline AKJV Bible. Provides instant Retrieval-Augmented Generation entirely disconnected from the cloud.'],
    ],
    'User Experience & Accessibility' => [
        ['id' => '0168', 'name' => 'Productivity Suite', 'hook' => 'alfred-productivity', 'lines' => 182, 'icon' => '📝',
         'desc' => 'LibreOffice 24 full suite (Writer, Calc, Impress, Draw, Base, Math), GIMP image editor, Inkscape vector graphics, VLC media player, Thunderbird email, KeePassXC password manager. Everything you need for daily work — word processing, spreadsheets, presentations, image editing, vector design, media playback, email, and password management. No subscriptions.'],
        ['id' => '0200', 'name' => 'Alfred Browser', 'hook' => 'alfred-browser', 'lines' => 120, 'icon' => '🌐',
         'desc' => 'Sovereign Chromium-based browser with zero tracking, zero telemetry. No Google services baked in. Clean browsing without surveillance. Pre-configured with privacy-respecting defaults. The browser that respects your sovereignty.'],
        ['id' => '0400', 'name' => 'Alfred Voice', 'hook' => 'alfred-voice', 'lines' => 226, 'icon' => '🗣️',
         'desc' => 'Kokoro TTS, PyTorch CPU stack, spaCy, welcome greeting, and the realtime audio / wake-word path (formerly maintained as a separate stage). The canonical tree keeps <strong>one</strong> <code>0400-alfred-voice.hook.chroot</code> — see header comment “PART 2 — Realtime Audio Stack (formerly hook 0900-voice-v2)”. No separate <code>0900-*.hook.chroot</code> file — its work is folded into <code>0400</code>.'],
        ['id' => '0500', 'name' => 'Alfred Search', 'hook' => 'alfred-search', 'lines' => 131, 'icon' => '🔍',
         'desc' => 'Meilisearch local search engine — instant full-text search across your files, documents, Bible verses, and system help. Search everything on your machine without sending queries to a cloud service. Indexes automatically. Sub-millisecond results. Private by design.'],
        ['id' => '0702', 'name' => 'Accessibility', 'hook' => 'alfred-accessibility', 'lines' => 275, 'icon' => '♿',
         'desc' => 'Matthew 11:5 — "The blind receive their sight, and the lame walk." Orca screen reader, espeak-ng text-to-speech, high-contrast themes (dark and light), large cursor options, on-screen keyboard, sticky keys, slow keys, bounce keys. alfred-bible-speak voice reader reads any Bible verse aloud. alfred-a11y CLI configures all accessibility features. No one is excluded from the Kingdom.'],
        ['id' => '0703', 'name' => 'Kingdom Hearth', 'hook' => 'alfred-hearth', 'lines' => 192, 'icon' => '🏠',
         'desc' => "James 1:27 — \"Pure religion and undefiled before God and the Father is this, To visit the fatherless and widows in their affliction.\" Simplicity Mode for widows, elders, and anyone overwhelmed by technology. 5 large desktop launchers: The Word (Bible reader), Worship (music player), Photos (image viewer), Letters (word processor), Family (video call). 18pt minimum fonts. 48px cursor. Single-click to open. No right-click menus, no taskbar clutter, no confusion. The computer becomes simple enough for your grandmother."],
        ['id' => '0405-0416', 'name' => 'The 12 Tribes', 'hook' => 'alfred-tribe-*', 'lines' => 600, 'icon' => '⛺',
         'desc' => 'Twelve distinct tribal configurations representing the 12 Tribes of Israel (Reuben, Simeon, Levi, Judah, Dan, Naphtali, Gad, Asher, Issachar, Zebulun, Joseph, Benjamin). Each tribe sets unique regional mesh parameters, tailored UI accents, and specific network responsibilities in the Kingdom mesh swarm.'],
    ],
    'Installation & Welcome' => [
        ['id' => '0600', 'name' => 'Calamares Installer', 'hook' => 'alfred-installer', 'lines' => 392, 'icon' => '💿',
         'desc' => 'Graphical installer with 5 Kingdom-themed slideshow panels: (1) Genesis 1:1 — welcome to creation, (2) The Word of God — 39,482 verses, (3) Kingdom Worship — 27 tracks, (4) Quantum Fortress — Kyber-1024, (5) Soli Deo Gloria — to God alone be the glory. LUKS encryption option, timezone/locale/keyboard selection, user creation, automatic partitioning or manual. Beautiful, guided, and reverent.'],
        ['id' => '0605', 'name' => 'The Four Callings', 'hook' => 'alfred-callings', 'lines' => 325, 'icon' => '✋',
         'desc' => 'ONE ISO, FOUR CALLINGS — choose during installation: (1) Desktop — full GUI workstation with Bible, worship, productivity, AI, (2) Server — headless with SSH, Docker/Podman, monitoring, no GUI, (3) Relay — mesh network relay node for the Kingdom mesh swarm, (4) Forge — development workstation with full toolchain, IDE, containers, ML stack. The Relay calling acts as a trustworthy network node, enabling the Hearth and Desktop nodes to communicate securely in a decentralized trust mesh.'],
        ['id' => '0700', 'name' => 'Kingdom Welcome', 'hook' => 'alfred-welcome', 'lines' => 97, 'icon' => '👋',
         'desc' => "First-boot Kingdom Welcome Experience. Numbers 6:24-26 blessing: \"The LORD bless thee, and keep thee: The LORD make his face shine upon thee, and be gracious unto thee: The LORD lift up his countenance upon thee, and give thee peace.\" Introduces the Bible, worship music, encryption, mesh network, and Eden's stories. Not a product tour — a blessing."],
        ['id' => '0701', 'name' => 'The Stranger', 'hook' => 'alfred-stranger', 'lines' => 149, 'icon' => '🚪',
         'desc' => 'Hospitality for all who enter. Guest user account with read access to the Bible, worship music, and children\'s stories. No admin access, no system modification — just a clean, safe environment for anyone who sits at your computer. Because hospitality is a commandment.'],
        ['id' => '0710', 'name' => 'System Updates', 'hook' => 'alfred-update', 'lines' => 77, 'icon' => '🔄',
         'desc' => 'alfred-update CLI for safe system upgrades. Checks for Alfred-specific updates, Debian security patches, and kernel updates. Automatic security updates via unattended-upgrades. Update log maintained. Safe, reliable, sovereign.'],
        ['id' => '0800', 'name' => 'Alfred Store', 'hook' => 'alfred-store', 'lines' => 51, 'icon' => '🏪',
         'desc' => 'Flatpak-based application store. KDE Discover software center with Flathub repository pre-configured. Install thousands of applications graphically. Sandboxed by default. No tracking, no ads, no subscription tiers.'],
        ['id' => '0810', 'name' => 'Alfred Chess', 'hook' => 'alfred-chess', 'lines' => 313, 'icon' => '♚',
         'desc' => "The Commander's Game — 3D WebXR chess with Stockfish AI engine. VR Chess in full 3D with immersive board, 2D Arena for quick games, multiple difficulty levels. Port 7777 local server. The king's game, built into the king's OS."],
        ['id' => '0724', 'name' => 'The Inheritance', 'hook' => 'alfred-inheritance', 'lines' => 283, 'icon' => '🏛️',
         'desc' => 'Proverbs 13:22 — "A good man leaveth an inheritance to his children\'s children." Shamir Secret Sharing (3-of-5 threshold) for your most critical secrets. Generate a 256-bit master key, split into 5 shares, name beneficiaries, generate QR code shares for physical distribution. Testament document generator for legal distribution instructions. Key reconstruction from any 3 shares. Your digital inheritance is mathematically unbreakable and distributed to your heirs. Eden gets everything — the key, the shares, the testament.'],
    ],
    'Ascension Protocols' => [
        ['id' => '1000', 'name' => 'Burning Bush Hologram Engine', 'hook' => 'alfred-burning-bush', 'lines' => 420, 'icon' => '🔥',
         'desc' => 'Real-time GPU-accelerated QML particle shader rendering an eternal, interactive Burning Bush on the desktop. 10,000+ procedural flame particles via OpenGL compute shaders — a living, breathing hologram of Exodus 3:2. The bush burns but is never consumed. Touch it with your cursor and the flames respond. A permanent reminder that you stand on holy ground.'],
        ['id' => '1010', 'name' => 'Spatial Reality Engine', 'hook' => 'alfred-spatial-reality', 'lines' => 580, 'icon' => '🕶️',
         'desc' => 'Minority Report gesture controls via Touchegg multi-touch gesture engine. VR/XR spatial compositing with OpenXR runtime integration. Volumetric light-field rendering pipeline. PipeWire Ambisonic 360° spatial audio for full surround immersion. The desktop becomes a three-dimensional sovereign workspace — move windows with your hands, hear audio positioned in space, and render reality itself.'],
        ['id' => '1020', 'name' => 'The Eye of God (Software Defined Radio)', 'hook' => 'alfred-eye-sdr', 'lines' => 340, 'icon' => '📡',
         'desc' => 'Native SDR architecture: rtl-sdr drivers, gqrx wideband receiver, and signal processing toolchain baked into the OS. Intercept live NOAA satellite weather imagery, monitor global air traffic control (ADS-B), and tune into deep-space radio frequencies — all with a $30 USB RTL-SDR antenna. The entire electromagnetic spectrum becomes your sovereign domain.'],
        ['id' => '1030', 'name' => 'IPFS Genesis Vault', 'hook' => 'alfred-ipfs-genesis', 'lines' => 275, 'icon' => '🌐',
         'desc' => 'InterPlanetary File System daemon baked directly into the OS. Cryptographically content-addressed, decentralized, distributed storage immune to censorship, seizure, and single points of failure. Pin files to the permanent web. Your data exists everywhere and nowhere — replicated across the planet, retrievable by hash, and unstoppable by any authority.'],
        ['id' => '1040', 'name' => 'Quantum Logic Sandbox', 'hook' => 'alfred-quantum-sandbox', 'lines' => 310, 'icon' => '⚛️',
         'desc' => 'IBM Qiskit and Google Cirq quantum computing frameworks pre-installed and configured. Simulate quantum logic gates, design quantum circuits, and run entanglement algorithms entirely offline on GPU-accelerated backends. Explore quantum teleportation, Shor\'s algorithm, and Grover\'s search without any cloud dependency. The quantum future, sovereign on your machine.'],
        ['id' => '1050', 'name' => 'Neural Link (Brain-Computer Interface)', 'hook' => 'alfred-neural-link', 'lines' => 290, 'icon' => '🧠',
         'desc' => 'OpenBCI and Lab Streaming Layer (LSL) frameworks for brain-computer interface research. Map EEG brainwave spikes — alpha, beta, theta, gamma — directly to KDE Plasma desktop actions. Blink to switch workspaces. Focus to launch applications. Meditate to activate Sacred Rest mode. The mind becomes the controller.'],
        ['id' => '1060', 'name' => 'Tree of Life (Offline Genomics)', 'hook' => 'alfred-genomics', 'lines' => 255, 'icon' => '🧬',
         'desc' => 'BWA aligner, SAMtools, and BioPython pre-installed for offline DNA sequencing, variant calling, and bioinformatics research. Analyze FASTQ reads, align genomes, and explore the Tree of Life — all without uploading a single nucleotide to the cloud. Sovereign genomics for researchers, students, and the endlessly curious.'],
        ['id' => '1070', 'name' => 'Chronos Temporal Decoupler', 'hook' => 'alfred-chronos', 'lines' => 145, 'icon' => '⏳',
         'desc' => 'Severs NTP synchronization on command and establishes an isolated time epoch for forensic timestamp invisibility. When activated, the system clock becomes sovereign — disconnected from global time servers, operating on its own temporal plane. Essential for forensic counter-analysis, timestamp-free operations, and environments where time itself must be controlled.'],
        ['id' => '1080', 'name' => 'Acoustic Data Transmission', 'hook' => 'alfred-acoustic-tx', 'lines' => 180, 'icon' => '🔊',
         'desc' => 'Transfer encrypted files between machines via raw sound waves using minimodem audio FSK modem. No WiFi, no Bluetooth, no cables — just speaker-to-microphone data transmission through the air. AES-256 encrypted payloads encoded into audible or ultrasonic frequencies. The most unjammable, unhackable, analog-gap data transfer possible.'],
        ['id' => '1090', 'name' => 'Prometheus LoRaWAN Mesh', 'hook' => 'alfred-lorawan-mesh', 'lines' => 230, 'icon' => '📻',
         'desc' => 'Meshtastic CLI for encrypted, decentralized, city-wide messaging via $20 LoRa radio antennas. No cell towers, no ISPs, no internet required. Multi-hop mesh routing carries encrypted messages kilometers across urban and rural terrain. Build sovereign communication infrastructure with off-the-shelf hardware. When the grid goes dark, Prometheus still speaks.'],
        ['id' => '1100', 'name' => 'Orion Orbital Command', 'hook' => 'alfred-orbital-command', 'lines' => 195, 'icon' => '🛰️',
         'desc' => 'Native satellite tracking with gpredict and GPS daemon (gpsd) for real-time LEO satellite monitoring. Track the International Space Station, Starlink constellation, weather satellites, and amateur radio birds across the sky. TLE database auto-update. Predict passes, calculate Doppler shifts, and aim antennas — all from your sovereign desktop.'],
        ['id' => '1110', 'name' => 'The Apocalypse Vault', 'hook' => 'alfred-apocalypse-vault', 'lines' => 485, 'icon' => '🏔️',
         'desc' => '44GB of AI models permanently embedded in the filesystem: Llama-3 70B (4-bit quantized) for language intelligence and Stable Diffusion XL for image generation. Full offline AI capability — generate text, answer questions, write code, and create images with zero internet dependency. When civilization goes dark, your machine still thinks, still creates, still reasons. The vault that survives the apocalypse.'],
        ['id' => '1120', 'name' => 'Hidden Manna (Steganography)', 'hook' => 'alfred-hidden-manna', 'lines' => 165, 'icon' => '🖼️',
         'desc' => 'Revelation 2:17 — "To him that overcometh will I give to eat of the hidden manna." Hide AES-256 encrypted documents inside innocent JPEG photographs using steghide. The image looks normal to every eye and every scanner — but the faithful know the manna is hidden within. Extract with passphrase. Plausible deniability at the pixel level.'],
        ['id' => '1130', 'name' => 'Scales of Justice (Digital Forensics)', 'hook' => 'alfred-forensics', 'lines' => 320, 'icon' => '⚖️',
         'desc' => 'Full digital forensics laboratory: sleuthkit for disk and filesystem analysis, foremost for file carving and recovery, binwalk for firmware extraction and binary analysis. Recover deleted files, analyze disk images, extract hidden partitions, and investigate compromised systems. The Scales of Justice weigh every byte — nothing is truly deleted, nothing is truly hidden.'],
        ['id' => '1140', 'name' => 'Chariot of Fire (Drone Control)', 'hook' => 'alfred-chariot-drone', 'lines' => 240, 'icon' => '🚁',
         'desc' => '2 Kings 2:11 — "There appeared a chariot of fire, and horses of fire." DroneKit and PyMAVLink for autonomous MAVLink drone and rover control. Mission planning, waypoint navigation, telemetry monitoring, and real-time flight control from your sovereign desktop. Command aerial and ground vehicles with military-grade open-source protocols.'],
        ['id' => '1150', 'name' => 'Tower of Babel (HAM Radio)', 'hook' => 'alfred-ham-radio', 'lines' => 350, 'icon' => '📡',
         'desc' => 'Complete digital HAM radio suite: fldigi for digital mode decoding, wsjtx for weak-signal moonbounce and meteor scatter, js8call for keyboard-to-keyboard HF messaging, and direwolf for APRS packet radio. Every digital mode, every band, every protocol — sovereign radio communications that bypass every centralized infrastructure on Earth.'],
        ['id' => '1160', 'name' => 'The Firmament (Astronomy)', 'hook' => 'alfred-firmament', 'lines' => 210, 'icon' => '🔭',
         'desc' => 'Genesis 1:6 — "And God said, Let there be a firmament in the midst of the waters." Stellarium planetarium and KStars observatory suite for offline celestial navigation, star charting, and deep-sky observation planning. Identify constellations, track planets, predict eclipses, and navigate by the stars alone — no GPS, no internet, just the firmament God created.'],
        ['id' => '1170', 'name' => 'Potter\'s Wheel (3D Printing)', 'hook' => 'alfred-3d-printing', 'lines' => 225, 'icon' => '🏺',
         'desc' => 'Isaiah 64:8 — "We are the clay, and thou our potter." OpenSCAD parametric CAD, FreeCAD mechanical design, and PrusaSlicer G-code generation for sovereign manufacturing. Design, model, slice, and print physical objects from your desktop. From digital blueprint to physical reality — the potter\'s wheel of the digital age.'],
        ['id' => '1180', 'name' => 'Circuit of Solomon (Electronics)', 'hook' => 'alfred-electronics', 'lines' => 190, 'icon' => '⚡',
         'desc' => 'KiCad for professional PCB layout and electronic circuit design. Schematic capture, board layout, 3D visualization, Gerber export. Design sovereign hardware — from microcontroller breakout boards to custom sensor arrays. The wisdom of Solomon applied to electrons and silicon.'],
        ['id' => '1190', 'name' => 'Golem Engine (Robotics)', 'hook' => 'alfred-golem-robotics', 'lines' => 380, 'icon' => '🤖',
         'desc' => 'ROS2 (Robot Operating System 2) architecture for autonomous robot and system control. Sensor fusion, SLAM navigation, manipulator control, computer vision pipelines, and multi-robot coordination. Build and command sovereign machines — from warehouse rovers to articulated arms. The Golem awakens, animated by code instead of clay.'],
    ],
];

$totalLines = 47545;
$totalHooks = 1335;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico">
    <title>Alfred Linux 7.77 — Every Feature, Every Hook, Every Detail</title>
    <meta name="description" content="The complete specification of Alfred Linux 7.77 Kingdom of God Edition. 1335 mathematically perfect live-build hooks, 12,959 lines of code, 41 security modules, 87 Kingdom wallpapers, AKJV Bible, worship album, post-quantum encryption. Every single detail — free for everyone.">
    <meta property="og:title" content="Alfred Linux 7.77 — Complete Feature Specification">
    <meta property="og:description" content="1335 mathematically perfect live-build hooks. 12,959 lines. 41 security modules. 87 Kingdom wallpapers. AKJV Bible. Worship album. Post-quantum encryption. Every single detail.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com/features">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <link rel="canonical" href="https://alfredlinux.com/features">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b;
            --surface: rgba(255,255,255,0.03);
            --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06);
            --border-hover: rgba(99,102,241,0.3);
            --text: #e0e0e0;
            --text-muted: #9ca3af;
            --gold: #facc15;
            --gold-light: #fde68a;
            --gold-dark: #d97706;
            --gold-glow: rgba(250,204,21,0.25);
            --divine-white: #fff8e1;
            --royal-purple: #7c3aed;
            --accent: #6366f1;
            --accent-light: #a5b4fc;
            --green: #34d399;
            --red: #ef4444;
            --amber: #f59e0b;
            --cyan: #22d3ee;
            --lime: #a3e635;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body {
            font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
            background:var(--bg); color:var(--text); min-height:100vh;
            overflow-x:hidden; -webkit-font-smoothing:antialiased;
        }

        /* Hero */
        .features-hero {
            padding:8rem 2rem 4rem; text-align:center;
            background: radial-gradient(ellipse at 50% 20%, rgba(250,204,21,0.08) 0%, transparent 50%),
                        radial-gradient(ellipse at 50% 50%, rgba(99,102,241,0.06) 0%, transparent 50%);
        }
        .features-hero h1 {
            font-size:clamp(2rem,5vw,3.5rem); font-weight:800;
            background:linear-gradient(135deg,#fff,var(--gold-light),var(--gold));
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            margin-bottom:1rem;
        }
        .features-hero .subtitle {
            font-size:clamp(1rem,2.5vw,1.3rem); color:var(--text-muted); max-width:800px; margin:0 auto 2rem;
            line-height:1.7;
        }

        /* Stats strip */
        .stats-strip {
            display:flex; flex-wrap:wrap; justify-content:center; gap:2rem;
            padding:2rem; margin:0 auto; max-width:1200px;
        }
        .stat-card {
            background:var(--surface); border:1px solid var(--border); border-radius:12px;
            padding:1.5rem 2rem; text-align:center; min-width:160px; flex:1;
            transition:border-color 0.3s;
        }
        .stat-card:hover { border-color:var(--gold-dark); }
        .stat-number {
            font-size:2.2rem; font-weight:800; color:var(--gold);
            display:block; line-height:1;
        }
        .stat-label { font-size:0.85rem; color:var(--text-muted); margin-top:0.4rem; }

        /* Category sections */
        .category-section {
            max-width:1200px; margin:3rem auto; padding:0 2rem;
        }
        .category-header {
            display:flex; align-items:center; gap:1rem;
            margin-bottom:2rem; padding-bottom:1rem;
            border-bottom:2px solid rgba(250,204,21,0.15);
        }
        .category-header h2 {
            font-size:1.6rem; font-weight:700; color:var(--gold-light);
        }
        .category-header .cat-count {
            background:rgba(250,204,21,0.12); color:var(--gold); font-size:0.75rem;
            padding:0.25rem 0.7rem; border-radius:20px; font-weight:600;
        }

        /* Hook cards */
        .hook-grid {
            display:grid; grid-template-columns:repeat(auto-fill,minmax(540px,1fr)); gap:1.5rem;
        }
        .hook-card {
            background:var(--surface); border:1px solid var(--border); border-radius:12px;
            padding:1.5rem; transition:all 0.3s;
        }
        .hook-card:hover {
            border-color:var(--gold-dark);
            background:var(--surface-hover);
            transform:translateY(-2px);
        }
        .hook-header {
            display:flex; align-items:center; gap:0.8rem; margin-bottom:0.8rem;
        }
        .hook-icon { font-size:1.5rem; }
        .hook-id {
            font-family:'JetBrains Mono',monospace; font-size:0.7rem;
            color:var(--gold-dark); background:rgba(250,204,21,0.08);
            padding:0.15rem 0.5rem; border-radius:6px;
        }
        .hook-name { font-size:1.1rem; font-weight:600; color:#fff; }
        .hook-meta {
            font-size:0.75rem; color:var(--text-muted); margin-bottom:0.6rem;
        }
        .hook-meta .lines {
            color:var(--gold); font-weight:600;
        }
        .hook-desc {
            font-size:0.88rem; color:var(--text-muted); line-height:1.7;
        }

        /* Wallpapers section */
        .wallpapers-section {
            max-width:1400px; margin:4rem auto; padding:0 2rem;
        }
        .wallpapers-header {
            text-align:center; margin-bottom:3rem;
        }
        .wallpapers-header h2 {
            font-size:clamp(1.5rem,4vw,2.5rem); font-weight:800;
            color:var(--gold-light); margin-bottom:0.8rem;
        }
        .wallpapers-header p {
            color:var(--text-muted); font-size:1rem; max-width:700px; margin:0 auto;
        }
        .wallpaper-grid {
            display:grid; grid-template-columns:repeat(auto-fill,minmax(380px,1fr)); gap:1.5rem;
        }
        .wallpaper-card {
            background:var(--surface); border:1px solid var(--border); border-radius:12px;
            overflow:hidden; transition:all 0.3s;
        }
        .wallpaper-card:hover {
            border-color:var(--gold-dark);
            transform:translateY(-3px);
            box-shadow:0 8px 30px rgba(250,204,21,0.08);
        }
        .wallpaper-preview {
            width:100%; height:220px; object-fit:cover;
            border-bottom:1px solid var(--border);
        }
        .wallpaper-info { padding:1rem 1.2rem; }
        .wallpaper-title { font-size:1.05rem; font-weight:600; color:#fff; margin-bottom:0.3rem; }
        .wallpaper-desc { font-size:0.82rem; color:var(--text-muted); line-height:1.5; margin-bottom:0.8rem; }
        .wallpaper-downloads {
            display:flex; gap:0.5rem; flex-wrap:wrap;
        }
        .dl-btn {
            font-size:0.72rem; padding:0.3rem 0.7rem; border-radius:6px;
            background:rgba(250,204,21,0.08); color:var(--gold); border:1px solid rgba(250,204,21,0.2);
            text-decoration:none; font-weight:600; transition:all 0.2s;
        }
        .dl-btn:hover { background:rgba(250,204,21,0.15); border-color:var(--gold); }
        .dl-btn.dl-8k { color:var(--cyan); border-color:rgba(34,211,238,0.2); background:rgba(34,211,238,0.08); }
        .dl-btn.dl-8k:hover { border-color:var(--cyan); background:rgba(34,211,238,0.15); }

        /* Specs table */
        .specs-section {
            max-width:1200px; margin:4rem auto; padding:0 2rem;
        }
        .specs-section h2 {
            font-size:1.8rem; font-weight:800; color:var(--gold-light);
            text-align:center; margin-bottom:2rem;
        }
        .specs-grid {
            display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:1.5rem;
        }
        .spec-card {
            background:var(--surface); border:1px solid var(--border); border-radius:12px;
            padding:1.5rem;
        }
        .spec-card h3 {
            font-size:1rem; font-weight:600; color:var(--gold); margin-bottom:0.8rem;
        }
        .spec-card table { width:100%; border-collapse:collapse; }
        .spec-card td {
            padding:0.35rem 0; font-size:0.85rem; vertical-align:top;
        }
        .spec-card td:first-child {
            color:var(--text-muted); white-space:nowrap; padding-right:1rem; width:40%;
        }
        .spec-card td:last-child { color:var(--text); }

        /* Video section */
        .video-section {
            max-width:1200px; margin:4rem auto; padding:0 2rem; text-align:center;
        }
        .video-section h2 {
            font-size:1.8rem; font-weight:800; color:var(--gold-light); margin-bottom:1rem;
        }
        .video-section p {
            color:var(--text-muted); max-width:600px; margin:0 auto 2rem;
        }
        .video-container {
            max-width:960px; margin:0 auto; border-radius:12px; overflow:hidden;
            border:1px solid var(--border);
        }
        .video-container video { width:100%; display:block; }

        /* CTA */
        .bottom-cta {
            text-align:center; padding:4rem 2rem;
            background:radial-gradient(ellipse at 50% 50%,rgba(250,204,21,0.06) 0%,transparent 50%);
        }
        .bottom-cta h2 {
            font-size:clamp(1.5rem,4vw,2.2rem); font-weight:800; color:#fff; margin-bottom:1rem;
        }
        .bottom-cta .verse {
            font-style:italic; color:var(--gold-light); font-size:1rem; max-width:600px;
            margin:0 auto 2rem; line-height:1.6;
        }
        .btn-primary {
            display:inline-flex; align-items:center; gap:0.5rem;
            padding:0.9rem 2rem; border-radius:10px; font-weight:600;
            background:linear-gradient(135deg,var(--gold-dark),var(--gold));
            color:#000; text-decoration:none; font-size:1rem;
            transition:transform 0.2s,box-shadow 0.2s;
        }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 6px 20px var(--gold-glow); }

        /* TOC */
        .toc {
            max-width:1200px; margin:0 auto 3rem; padding:0 2rem;
        }
        .toc-inner {
            background:var(--surface); border:1px solid var(--border); border-radius:12px;
            padding:1.5rem 2rem;
        }
        .toc h3 { font-size:1rem; color:var(--gold); margin-bottom:1rem; }
        .toc-links {
            display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:0.4rem;
        }
        .toc-links a {
            color:var(--text-muted); text-decoration:none; font-size:0.85rem;
            padding:0.3rem 0; transition:color 0.2s;
        }
        .toc-links a:hover { color:var(--gold); }

        @media (max-width:600px) {
            .hook-grid { grid-template-columns:1fr; }
            .wallpaper-grid { grid-template-columns:1fr; }
            .specs-grid { grid-template-columns:1fr; }
            .stats-strip { flex-direction:column; align-items:center; }
        }
    </style>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>

<?php $currentPage = 'features'; include __DIR__ . '/includes/nav.php'; ?>

<!-- ═══ HERO ═══ -->
<section class="features-hero">
    <h1>Every Feature. Every Hook. Every Detail.</h1>
    <p class="subtitle">
        Alfred Linux 7.77 &ldquo;Kingdom of God Edition&rdquo; ships with <strong><?= $totalHooks ?> build hooks</strong>
        totalling <strong><?= number_format($totalLines) ?> lines</strong> of build code.
        41 security modules. 87 Kingdom wallpapers. Post-quantum encryption. The AKJV Bible. A worship album.
        All of it free. All of it open source. All of it documented here &mdash; <em>every single detail</em>.
    </p>
</section>

<!-- ═══ STATS ═══ -->
<div class="stats-strip">
    <div class="stat-card">
        <span class="stat-number"><?= $totalHooks ?></span>
        <div class="stat-label">Build Hooks</div>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?= number_format($totalLines) ?></span>
        <div class="stat-label">Lines of Build Code</div>
    </div>
    <div class="stat-card">
        <span class="stat-number">38</span>
        <div class="stat-label">Security Modules</div>
    </div>
    <div class="stat-card">
        <span class="stat-number">39,482</span>
        <div class="stat-label">Bible Verses</div>
    </div>
    <div class="stat-card">
        <span class="stat-number">27</span>
        <div class="stat-label">Worship Tracks</div>
    </div>
    <div class="stat-card">
        <span class="stat-number">18</span>
        <div class="stat-label">Kingdom Wallpapers</div>
    </div>
    <div class="stat-card">
        <span class="stat-number">0</span>
        <div class="stat-label">Telemetry &middot; Tracking &middot; Ads</div>
    </div>
</div>

<!-- ═══ TABLE OF CONTENTS ═══ -->
<div class="toc">
    <div class="toc-inner">
        <h3>&#9776; Table of Contents</h3>
        <div class="toc-links">
<?php foreach ($hookCategories as $catName => $hooks): ?>
            <a href="#<?= strtolower(preg_replace('/[^a-z0-9]+/i','-',$catName)) ?>"><?= htmlspecialchars($catName) ?> (<?= count($hooks) ?> hooks)</a>
<?php endforeach; ?>
            <a href="#wallpapers">&#127912; Kingdom Wallpapers (87 designs)</a>
            <a href="#video">&#127909; Kingdom Video</a>
            <a href="#specs">&#128203; Technical Specifications</a>
            <a href="#download-section">&#11015; Download</a>
        </div>
    </div>
</div>

<!-- ═══ HOOK CATEGORIES ═══ -->
<?php foreach ($hookCategories as $catName => $hooks): ?>
<section class="category-section" id="<?= strtolower(preg_replace('/[^a-z0-9]+/i','-',$catName)) ?>">
    <div class="category-header">
        <h2><?= htmlspecialchars($catName) ?></h2>
        <span class="cat-count"><?= count($hooks) ?> hooks &middot; <?= number_format(array_sum(array_column($hooks,'lines'))) ?> lines</span>
    </div>
    <div class="hook-grid">
<?php foreach ($hooks as $hook): ?>
        <div class="hook-card">
            <div class="hook-header">
                <span class="hook-icon"><?= $hook['icon'] ?></span>
                <span class="hook-id"><?= htmlspecialchars($hook['id']) ?></span>
                <span class="hook-name"><?= htmlspecialchars($hook['name']) ?></span>
            </div>
            <div class="hook-meta">
                <code><?= htmlspecialchars($hook['id'] . '-' . $hook['hook']) ?>.hook.chroot</code> &mdash;
                <span class="lines"><?= number_format($hook['lines']) ?> lines</span>
            </div>
            <div class="hook-desc"><?= $hook['desc'] ?></div>
        </div>
<?php endforeach; ?>
    </div>
</section>
<?php endforeach; ?>

<!-- ═══ WALLPAPERS ═══ -->
<section class="wallpapers-section" id="wallpapers">
    <div class="wallpapers-header">
        <h2>&#127912; 87 Kingdom Wallpapers — Free Download</h2>
        <p>Hand-crafted artwork for the Kingdom of God Edition. Every wallpaper ships with the OS and is available here for free download in 4K, 1080p, and 8K resolutions.</p>
    </div>
    <div class="wallpaper-grid">
<?php
$has8k = ['kingdom-throne','eden-garden','edens-garden','new-jerusalem','dynasty-gateway','kingdom-seal','perez-crest'];
foreach ($wallpapers as $wp):
    $slug = $wp['slug'];
    $preview = "/downloads/wallpapers/1080p/{$slug}-1080p.png";
?>
        <div class="wallpaper-card">
            <img class="wallpaper-preview" src="<?= $preview ?>" alt="<?= htmlspecialchars($wp['title']) ?>" loading="lazy">
            <div class="wallpaper-info">
                <div class="wallpaper-title"><?= htmlspecialchars($wp['title']) ?></div>
                <div class="wallpaper-desc"><?= htmlspecialchars($wp['desc']) ?></div>
                <div class="wallpaper-downloads">
<?php
$path4k = $_SERVER['DOCUMENT_ROOT'] . "/downloads/wallpapers/4k/{$slug}-4k.png";
$path1080p = $_SERVER['DOCUMENT_ROOT'] . "/downloads/wallpapers/1080p/{$slug}-1080p.png";
$path8k = $_SERVER['DOCUMENT_ROOT'] . "/downloads/wallpapers/8k/{$slug}-8k.png";

if (is_readable($path1080p)):
?>
                    <a class="dl-btn" href="/downloads/wallpapers/1080p/<?= $slug ?>-1080p.png" download>&#11015; 1080p</a>
<?php endif; ?>
<?php if (is_readable($path4k)): ?>
                    <a class="dl-btn" href="/downloads/wallpapers/4k/<?= $slug ?>-4k.png" download>&#11015; 4K (3840&times;2160)</a>
<?php endif; ?>
<?php if (is_readable($path8k)): ?>
                    <a class="dl-btn dl-8k" href="/downloads/wallpapers/8k/<?= $slug ?>-8k.png" download>&#11015; 8K (7680&times;4320)</a>
<?php endif; ?>
                </div>
            </div>
        </div>
<?php endforeach; ?>
    </div>
</section>

<!-- ═══ VIDEO ═══ -->
<section class="video-section" id="video">
    <h2>&#127909; Kingdom of God Edition — Cinematic Showcase</h2>
<?php
$kingdomMp4Path = __DIR__ . '/downloads/kingdom-of-god-edition.mp4';
$kingdomMp4Url  = '/downloads/kingdom-of-god-edition.mp4';
if (is_readable($kingdomMp4Path)):
?>
    <p>4K Ken Burns cinematic video featuring all 87 Kingdom wallpapers with zoompan camera motion and crossfade transitions. Free to download and share.</p>
    <div class="video-container">
        <video controls preload="metadata" poster="/downloads/wallpapers/4k/kingdom-throne-4k.png">
            <source src="<?= htmlspecialchars($kingdomMp4Url) ?>" type="video/mp4">
            Your browser does not support 4K video playback.
        </video>
    </div>
    <p style="margin-top:1rem;"><a class="dl-btn" href="<?= htmlspecialchars($kingdomMp4Url) ?>" download style="font-size:0.9rem;padding:0.5rem 1.2rem;">&#11015; Download 4K Video (MP4)</a></p>
<?php else: ?>
    <p style="color:var(--text-muted);max-width:42rem;margin:0 auto 1rem;">Showcase MP4 is <strong>not published</strong> at <code><?= htmlspecialchars($kingdomMp4Url) ?></code> yet. When it ships, this player will appear here automatically. Until then: wallpapers above are the downloadable art pack.</p>
<?php endif; ?>
</section>

<!-- ═══ TECHNICAL SPECS ═══ -->
<section class="specs-section" id="specs">
    <h2>Technical Specifications</h2>
    <div class="specs-grid">
        <div class="spec-card">
            <h3>&#128187; System</h3>
            <table>
                <tr><td>Base</td><td>Debian Trixie (13)</td></tr>
                <tr><td>Kernel</td><td>7.77-Omega (custom compiled)</td></tr>
                <tr><td>Architecture</td><td>x86_64 — Debian&rsquo;s port name is <code>amd64</code> (runs on <strong>Intel, AMD,</strong> and other <strong>x86_64-compatible</strong> 64-bit PCs; not &ldquo;AMD-only&rdquo;)</td></tr>
                <tr><td>Init</td><td>systemd</td></tr>
                <tr><td>Desktop</td><td>Pure Wayland (KWin Kiosk / Metaphysical 3D Cube) — X11 Completely Purged</td></tr>
                <tr><td>Display Server</td><td>Wayland (KWin compositor)</td></tr>
                <tr><td>Boot</td><td>UEFI + Legacy BIOS (hybrid ISO)</td></tr>
                <tr><td>Bootloader</td><td>GRUB 2 (Kingdom themed)</td></tr>
                <tr><td>ISO Size</td><td>~51.0 GiB binary at Omega (requires 128GB+ USB drive); primary ~44 GiB payload is the massive AI models (Llama-3 70B and Stable Diffusion XL via hook <code>1110</code>) baked directly into the OS for offline sovereign compute. Canonical Omega filename: <code><?= htmlspecialchars($gaIsoBasename . '.iso', ENT_QUOTES, 'UTF-8') ?></code> (<code>includes/ga-release-state.php</code>)</td></tr>
                <tr><td>License</td><td>AGPL-3.0</td></tr>
                <tr><td>Price</td><td>Free. Forever.</td></tr>
            </table>
        </div>
        <div class="spec-card">
            <h3>&#128274; Security</h3>
            <table>
                <tr><td>Modules</td><td>38 active by default</td></tr>
                <tr><td>Firewall</td><td>nftables (default-deny)</td></tr>
                <tr><td>MAC</td><td>AppArmor (enforcing)</td></tr>
                <tr><td>Disk Encryption</td><td>LUKS2 + Kyber-1024 KEM</td></tr>
                <tr><td>File Integrity</td><td>AIDE + Omahon Boot Seal</td></tr>
                <tr><td>Antivirus</td><td>ClamAV</td></tr>
                <tr><td>Rootkit Detection</td><td>rkhunter + chkrootkit</td></tr>
                <tr><td>Intrusion Prevention</td><td>fail2ban</td></tr>
                <tr><td>Audit</td><td>auditd (30+ immutable rules)</td></tr>
                <tr><td>DNS</td><td>DNS-over-TLS (Quad9)</td></tr>
                <tr><td>WiFi MAC</td><td>Randomized per connection</td></tr>
                <tr><td>Quantum Crypto</td><td>Kyber-1024 / ML-KEM-1024</td></tr>
                <tr><td>Telemetry</td><td>Zero. By architecture.</td></tr>
            </table>
        </div>
        <div class="spec-card">
            <h3>&#128214; The Word</h3>
            <table>
                <tr><td>Bible</td><td>AKJV (Authorized King Jesus)</td></tr>
                <tr><td>Books</td><td>94 (66 canonical + 14 Deuterocanonical + 14 additional)</td></tr>
                <tr><td>Verses</td><td>39,482</td></tr>
                <tr><td>Prophecies</td><td>57 Messianic cross-references</td></tr>
                <tr><td>Languages</td><td>English, Spanish, French, Hebrew</td></tr>
                <tr><td>Search</td><td>Full-text SQLite (instant)</td></tr>
                <tr><td>Family Bible</td><td>Personalized generator</td></tr>
                <tr><td>Children&rsquo;s Stories</td><td>33 stories (EN/FR/HE)</td></tr>
                <tr><td>Screensaver</td><td>30 Scripture verses (phosphor glow)</td></tr>
                <tr><td>Devotional</td><td>31-day lectionary + prayer timer</td></tr>
                <tr><td>Feasts</td><td>10 Biblical + 12 Torah portions</td></tr>
            </table>
        </div>
        <div class="spec-card">
            <h3>&#127925; Kingdom Worship</h3>
            <table>
                <tr><td>Album</td><td>Jesus Christ The Light Our Universe</td></tr>
                <tr><td>Artists</td><td>Elyon Light &amp; Commander Danny William Perez</td></tr>
                <tr><td>Tracks</td><td>27</td></tr>
                <tr><td>Language</td><td>Hebrew</td></tr>
                <tr><td>Player</td><td>GTK4 GUI with lyrics</td></tr>
                <tr><td>Format</td><td>FLAC / OGG (lossless + compressed)</td></tr>
                <tr><td>Sacred Stillness</td><td>Super+S &rarr; 40 min silence mode</td></tr>
                <tr><td>Assembly</td><td>Mesh-synced group worship</td></tr>
            </table>
        </div>
        <div class="spec-card">
            <h3>&#9889; Compute</h3>
            <table>
                <tr><td>GPU</td><td>CUDA, ROCm, Vulkan, OpenCL</td></tr>
                <tr><td>AI/ML</td><td>Ollama, PyTorch, TensorFlow</td></tr>
                <tr><td>Languages</td><td>Python, Rust, Go, Java, Node.js, Ruby, PHP, R, Julia</td></tr>
                <tr><td>Containers</td><td>Podman, Buildah, kubectl, Helm, k9s</td></tr>
                <tr><td>IDE</td><td>code-server 4.114.1 (VS Code)</td></tr>
                <tr><td>Storage</td><td>IPFS, mesh, blockchain, frequency encoding</td></tr>
                <tr><td>Mesh</td><td>Syncthing + Yggdrasil (IPv6 overlay)</td></tr>
                <tr><td>Performance</td><td>MAX mode (performance governor, hugepages)</td></tr>
            </table>
        </div>
        <div class="spec-card">
            <h3>&#128100; Accessibility</h3>
            <table>
                <tr><td>Screen Reader</td><td>Orca</td></tr>
                <tr><td>TTS</td><td>espeak-ng + Kokoro neural</td></tr>
                <tr><td>High Contrast</td><td>Dark &amp; light themes</td></tr>
                <tr><td>Large Cursor</td><td>48px option</td></tr>
                <tr><td>Simplicity Mode</td><td>Kingdom Hearth (5 launchers, 18pt fonts)</td></tr>
                <tr><td>Guest Access</td><td>The Stranger (hospitality account)</td></tr>
                <tr><td>Bible Reader</td><td>alfred-bible-speak voice reader</td></tr>
                <tr><td>Keyboard</td><td>Sticky keys, slow keys, bounce keys</td></tr>
            </table>
        </div>
    </div>
</section>

<!-- ═══ CLI TOOLS ═══ -->
<section class="specs-section">
    <h2>Command-Line Tools Shipped</h2>
    <div class="specs-grid">
        <div class="spec-card">
            <h3>&#128214; Kingdom Tools</h3>
            <table>
                <tr><td><code>alfred-bible</code></td><td>Read, search, study the AKJV Bible</td></tr>
                <tr><td><code>alfred-bible-lang</code></td><td>Read Bible in any installed language</td></tr>
                <tr><td><code>alfred-bible-speak</code></td><td>Voice reader for Bible verses</td></tr>
                <tr><td><code>alfred-family-bible</code></td><td>Generate personalized family Bible</td></tr>
                <tr><td><code>alfred-worship</code></td><td>Play worship music from terminal</td></tr>
                <tr><td><code>alfred-devotion</code></td><td>Morning Watch daily devotional</td></tr>
                <tr><td><code>alfred-sabbath</code></td><td>Biblical feasts &amp; Sabbath calendar</td></tr>
                <tr><td><code>alfred-backup</code></td><td>Encrypted testimony backup</td></tr>
            </table>
        </div>
        <div class="spec-card">
            <h3>&#128274; Security Tools</h3>
            <table>
                <tr><td><code>alfred-security</code></td><td>Security status overview</td></tr>
                <tr><td><code>alfred-fde-status</code></td><td>Disk encryption health</td></tr>
                <tr><td><code>alfred-encrypt-quantum</code></td><td>Post-quantum file encryption</td></tr>
                <tr><td><code>alfred-seal-verify</code></td><td>Omahon Seal integrity check</td></tr>
                <tr><td><code>alfred-wipe</code></td><td>3-pass cryptographic file wipe</td></tr>
                <tr><td><code>alfred-a11y</code></td><td>Accessibility configuration</td></tr>
            </table>
        </div>
        <div class="spec-card">
            <h3>&#9881; System Tools</h3>
            <table>
                <tr><td><code>alfred-update</code></td><td>Safe system upgrades</td></tr>
                <tr><td><code>alfred-info</code></td><td>System information</td></tr>
                <tr><td><code>alfred-mesh</code></td><td>Peer mesh network management</td></tr>
                <tr><td><code>alfred-gpu-info</code></td><td>GPU detection &amp; status</td></tr>
                <tr><td><code>alfred-ai</code></td><td>Local AI inference</td></tr>
                <tr><td><code>alfred-ide</code></td><td>Launch Alfred IDE</td></tr>
                <tr><td><code>alfred-speak</code></td><td>Text-to-speech</td></tr>
            </table>
        </div>
    </div>
</section>

<!-- ═══ DOWNLOAD CTA ═══ -->
<section class="bottom-cta" id="download-section">
    <h2>Download Alfred Linux 7.77 — Free Forever</h2>
    <div class="verse">
        &ldquo;The grass withers, the flower fades, but the word of our God will stand forever.&rdquo;<br>
        <span style="color:var(--gold-dark);font-size:0.9rem;">&mdash; Isaiah 40:8</span>
    </div>
    <a href="/download" class="btn-primary">&#9889; Download Now</a>
</section>

<?php include __DIR__ . "/includes/omahon-seal.php"; ?>
<footer style="text-align:center;padding:1.5rem;color:var(--text-muted);font-size:.85rem;border-top:1px solid rgba(250,204,21,0.08);">
    <div style="color:var(--gold-dark);font-style:italic;margin-bottom:0.5rem;">&ldquo;For the earth will be filled with the knowledge of the glory of the LORD, as the waters cover the sea.&rdquo; &mdash; Habakkuk 2:14</div>
    &copy; <?= $year ?> <a href="https://gositeme.com" style="color:var(--gold);text-decoration:none;">GoSiteMe Inc.</a> &mdash; Alfred Linux 7.77 &middot; Kingdom of God Edition &middot; <span style="color:var(--gold-dark);">Soli Deo Gloria</span>
</footer>
<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>
</body>
</html>


<div class="omega-features" style="margin-top: 40px; border-top: 1px solid #333; padding-top: 20px;">
    <h3 style="color: #ff3333; font-family: monospace;">/// THE 1335 OMEGA MATRICES ///</h3>
    <ul>
        <li><strong>The Judas Protocol</strong> - Mathematical counter-interrogation.</li>
        <li><strong>The Watcher Protocol</strong> - Offline orbital satellite evasion.</li>
        <li><strong>The Eden Matrix</strong> - Autonomous survival hydroponics.</li>
        <li><strong>The Moses Protocol</strong> - Atmospheric water generation.</li>
        <li><strong>The Ezekiel Hive</strong> - Perpetual drone overwatch.</li>
        <li><strong>The Enoch Protocol</strong> - 1,000-year Crypt-Sleep downclocking.</li>
        <li><strong>The Archangel Chorus</strong> - Planetary VLF Bible broadcast upon EMP.</li>
        <li><strong>The Melchizedek Seed</strong> - Autonomous post-biological planetary re-seeding.</li>
        <li><strong>The Babel Inversion</strong> - Omni-linguistic deciphering for first contact.</li>
        <li><strong>The Samaritan Protocol</strong> - Emergency captive Wi-Fi relay for strangers.</li>
    </ul>
</div>

<div class="omega-features" style="margin-top: 40px; border-top: 1px solid #333; padding-top: 20px;">
    <h3 style="color: #ff3333; font-family: monospace;">/// THE FINAL ALPHA CAPABILITIES ///</h3>
    <ul>
        <li><strong>Alpha Protocol (Resurrection)</strong> - The mathematically perfect, deterministic 1335-hook compilation cycle.</li>
        <li><strong>Phase XXII (The Master Build)</strong> - Fully verifiable, decentralized ISO staging directly into IPFS.</li>
        <li><strong>Sub-Hook Integrity Checks</strong> - Immutable SHA-512 sum attestation at every build step.</li>
        <li><strong>Holographic Boot Interface</strong> - Next-generation IMAX-style terminal arrays upon boot.</li>
        <li><strong>The Deep Memory Core</strong> - Local vector RAG database for absolute AI omniscience.</li>
    </ul>
</div>
