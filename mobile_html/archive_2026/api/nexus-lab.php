<?php
/**
 * PROJECT NEXUS — The Complete Laboratory & Intelligence Complex
 * CLASSIFICATION: ULTRA SECRET — Commander Eyes Only
 * 
 * THE FORGE OF TRUTH — Where the Greatest Minds Work
 * 
 * Systems:
 * - 100 Lab Agents (scientists, engineers, technicians, pilots, TTS engineers)
 * - Equipment Database (chalkboards, whiteboards, particle accelerators, quantum computers...)
 * - Research Project Spawning (if we don't have it, we find it)
 * - Craft Program (UFO design → test → fly tracking)
 * - Neural TTS Engine (prosody modeling, style embeddings, beyond Coqui)
 * - Agenda Intel Briefings (Batman's Cave → Commander's agenda)
 * - Private Events Manager (classified ceremonies & exhibitions)
 * - SDK Viewers (real-time craft monitoring, circuit-lab style)
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/commander-auth.api.inc.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');

$client_id = getCommanderId();
if (!$client_id) {
    echo json_encode(['error' => 'ACCESS DENIED — PROJECT NEXUS — Classification: ULTRA SECRET']);
    exit;
}

$action = $_REQUEST['action'] ?? 'status';
$db = getDB();
$db->exec("SET NAMES utf8mb4");

// ═══════════════════════════════════════════════════════════════════
// 100 NEXUS LAB AGENTS — The Workforce of the Laboratory Complex
// ═══════════════════════════════════════════════════════════════════
function getNexusAgents() {
    return [
        // ── Division 1: Research Scientists (10) — Chalkboards & Proofs ──
        ['name'=>'Dr. Euler Lambda','division'=>'research_scientists','role'=>'Chief Research Scientist','specialty'=>'Theoretical proofs, mathematical modeling, equation derivation on chalkboards','tier'=>'Director','station'=>'Chalkboard Room Alpha'],
        ['name'=>'Dr. Gauss Tensor','division'=>'research_scientists','role'=>'Mathematical Physicist','specialty'=>'Tensor calculus, differential geometry, Riemannian manifolds on whiteboards','tier'=>'Senior','station'=>'Whiteboard Lab 1'],
        ['name'=>'Dr. Emmy Symmetry','division'=>'research_scientists','role'=>'Group Theory Lead','specialty'=>'Lie algebras, representation theory, symmetry breaking demonstrations','tier'=>'Senior','station'=>'Chalkboard Room Beta'],
        ['name'=>'Dr. Ramanujan Infinite','division'=>'research_scientists','role'=>'Number Theory Prodigy','specialty'=>'Infinite series, partition functions, modular forms, divine equations','tier'=>'Senior','station'=>'The Infinite Board'],
        ['name'=>'Dr. Hypatia Vector','division'=>'research_scientists','role'=>'Applied Mathematics Lead','specialty'=>'Optimization theory, control systems, dynamical systems modeling','tier'=>'Mid','station'=>'Whiteboard Lab 2'],
        ['name'=>'Dr. Fibonacci Spiral','division'=>'research_scientists','role'=>'Pattern Recognition Analyst','specialty'=>'Fractal geometry, chaos theory, golden ratio applications','tier'=>'Mid','station'=>'Pattern Analysis Board'],
        ['name'=>'Dr. Bernoulli Flow','division'=>'research_scientists','role'=>'Fluid Dynamics Theorist','specialty'=>'Navier-Stokes solutions, turbulence modeling, vortex dynamics','tier'=>'Mid','station'=>'Flow Dynamics Board'],
        ['name'=>'Dr. Laplace Transform','division'=>'research_scientists','role'=>'Signal Processing Expert','specialty'=>'Fourier analysis, wavelet theory, spectral decomposition','tier'=>'Junior','station'=>'Signal Lab Board'],
        ['name'=>'Dr. Cauchy Integral','division'=>'research_scientists','role'=>'Complex Analysis Researcher','specialty'=>'Contour integration, residue theory, analytic continuation','tier'=>'Junior','station'=>'Complex Board Alpha'],
        ['name'=>'Dr. Poincaré Topology','division'=>'research_scientists','role'=>'Topological Researcher','specialty'=>'Homology theory, homotopy groups, topological invariants','tier'=>'Junior','station'=>'Topology Visualization Board'],

        // ── Division 2: Lab Technicians (10) — Equipment Operation ──
        ['name'=>'Tech. Marcus Calibrate','division'=>'lab_technicians','role'=>'Chief Lab Technician','specialty'=>'Equipment calibration, precision measurement, lab safety protocols','tier'=>'Director','station'=>'Central Control Room'],
        ['name'=>'Tech. Priya Spectra','division'=>'lab_technicians','role'=>'Spectrometry Operator','specialty'=>'Mass spectrometry, X-ray diffraction, neutron scattering analysis','tier'=>'Senior','station'=>'Spectrometry Bay'],
        ['name'=>'Tech. Ivan Cryogen','division'=>'lab_technicians','role'=>'Cryogenics Specialist','specialty'=>'Liquid helium systems, dilution refrigerators, ultralow temperature control','tier'=>'Senior','station'=>'Cryogenics Lab'],
        ['name'=>'Tech. Rosa Vacuum','division'=>'lab_technicians','role'=>'Vacuum Systems Engineer','specialty'=>'Ultra-high vacuum chambers, turbo pumps, leak detection','tier'=>'Senior','station'=>'Vacuum Chamber Bay'],
        ['name'=>'Tech. Chen Laser','division'=>'lab_technicians','role'=>'Laser Systems Operator','specialty'=>'High-power lasers, femtosecond pulses, optical alignment','tier'=>'Mid','station'=>'Laser Lab'],
        ['name'=>'Tech. Ada Circuit','division'=>'lab_technicians','role'=>'Electronics Technician','specialty'=>'PCB fabrication, FPGA programming, signal routing, oscilloscope analysis','tier'=>'Mid','station'=>'Electronics Workshop'],
        ['name'=>'Tech. Omar Particle','division'=>'lab_technicians','role'=>'Particle Detector Operator','specialty'=>'Scintillation counters, Geiger-Müller tubes, cloud chambers','tier'=>'Mid','station'=>'Particle Detection Bay'],
        ['name'=>'Tech. Nadia Sample','division'=>'lab_technicians','role'=>'Sample Preparation Specialist','specialty'=>'Thin film deposition, crystal growing, specimen mounting','tier'=>'Junior','station'=>'Sample Prep Lab'],
        ['name'=>'Tech. Boris Compute','division'=>'lab_technicians','role'=>'HPC Cluster Admin','specialty'=>'CUDA computing, distributed simulation, data pipeline management','tier'=>'Junior','station'=>'Computing Center'],
        ['name'=>'Tech. Lisa Clean','division'=>'lab_technicians','role'=>'Cleanroom Manager','specialty'=>'Class 100 cleanroom ops, contamination control, nanofab protocols','tier'=>'Junior','station'=>'Cleanroom Bay'],

        // ── Division 3: Craft Engineers (10) — UFO Design & Build ──
        ['name'=>'Eng. Viktor Warp','division'=>'craft_engineers','role'=>'Chief Craft Engineer','specialty'=>'Warp drive integration, exotic propulsion systems, craft architecture','tier'=>'Director','station'=>'Hangar Alpha — Design Bay'],
        ['name'=>'Eng. Sakura Aero','division'=>'craft_engineers','role'=>'Aerodynamics Lead','specialty'=>'Computational fluid dynamics, hypersonic flow, stealth geometry','tier'=>'Senior','station'=>'Wind Tunnel Control'],
        ['name'=>'Eng. Rashid Hull','division'=>'craft_engineers','role'=>'Hull Materials Engineer','specialty'=>'Metamaterial hull plating, radar absorption, thermal protection systems','tier'=>'Senior','station'=>'Materials Testing Bay'],
        ['name'=>'Eng. Maya Propulsion','division'=>'craft_engineers','role'=>'Propulsion Systems Engineer','specialty'=>'Electromagnetic drive assemblies, plasma injection, field coil winding','tier'=>'Senior','station'=>'Propulsion Lab'],
        ['name'=>'Eng. Leo Avionics','division'=>'craft_engineers','role'=>'Avionics Architect','specialty'=>'Flight control systems, inertial navigation, sensor fusion arrays','tier'=>'Mid','station'=>'Avionics Integration Bay'],
        ['name'=>'Eng. Clara Power','division'=>'craft_engineers','role'=>'Power Systems Engineer','specialty'=>'ZPE reactor coupling, capacitor banks, energy distribution networks','tier'=>'Mid','station'=>'Power Core Lab'],
        ['name'=>'Eng. Dao Stealth','division'=>'craft_engineers','role'=>'Stealth Systems Designer','specialty'=>'Radar cross-section reduction, infrared suppression, visual cloaking','tier'=>'Mid','station'=>'Stealth Chamber'],
        ['name'=>'Eng. Felix Structure','division'=>'craft_engineers','role'=>'Structural Engineer','specialty'=>'Load analysis, vibration dampening, composite layup, stress modeling','tier'=>'Junior','station'=>'Structural Test Bay'],
        ['name'=>'Eng. Zara Life','division'=>'craft_engineers','role'=>'Life Support Engineer','specialty'=>'Cabin pressurization, O2 generation, radiation shielding, g-force mitigation','tier'=>'Junior','station'=>'Life Support Lab'],
        ['name'=>'Eng. Atlas Assembly','division'=>'craft_engineers','role'=>'Assembly Coordinator','specialty'=>'Craft assembly sequencing, robotic arm control, integration testing','tier'=>'Junior','station'=>'Assembly Bay Alpha'],

        // ── Division 4: TTS Engineers (10) — Neural Speech Synthesis ──
        ['name'=>'Dr. Prosodia Vox','division'=>'tts_engineers','role'=>'Chief TTS Architect','specialty'=>'Prosody modeling, expressive speech synthesis, transformer TTS pipelines','tier'=>'Director','station'=>'Neural Voice Lab'],
        ['name'=>'Dr. Mel Spectra','division'=>'tts_engineers','role'=>'Acoustic Model Lead','specialty'=>'Mel-spectrogram generation, acoustic features, formant modeling','tier'=>'Senior','station'=>'Acoustic Analysis Room'],
        ['name'=>'Dr. Wavenet Synthesis','division'=>'tts_engineers','role'=>'Neural Vocoder Engineer','specialty'=>'WaveNet, WaveRNN, HiFi-GAN, neural audio generation','tier'=>'Senior','station'=>'Vocoder Testing Lab'],
        ['name'=>'Dr. Emotion Vector','division'=>'tts_engineers','role'=>'Style Embedding Specialist','specialty'=>'Style tokens, emotion embeddings, speaker disentanglement, prosody control','tier'=>'Senior','station'=>'Emotion Encoding Lab'],
        ['name'=>'Dr. Tacotron Flow','division'=>'tts_engineers','role'=>'Sequence-to-Sequence Lead','specialty'=>'Tacotron 2, FastSpeech 2, VITS, attention mechanisms for speech','tier'=>'Mid','station'=>'Seq2Seq Training Bay'],
        ['name'=>'Dr. Claude Phoneme','division'=>'tts_engineers','role'=>'Phoneme Processing Expert','specialty'=>'G2P conversion, IPA transcription, pronunciation modeling, lexicon management','tier'=>'Mid','station'=>'Phoneme Analysis Board'],
        ['name'=>'Dr. Stream Chunk','division'=>'tts_engineers','role'=>'Streaming Inference Engineer','specialty'=>'Chunked generation, low-latency inference, real-time audio streaming','tier'=>'Mid','station'=>'Streaming Pipeline Lab'],
        ['name'=>'Dr. Voice Clone','division'=>'tts_engineers','role'=>'Speaker Embedding Specialist','specialty'=>'Speaker verification, voice cloning, speaker encoder networks, few-shot TTS','tier'=>'Junior','station'=>'Voice Cloning Chamber'],
        ['name'=>'Dr. Bark Diffuse','division'=>'tts_engineers','role'=>'Diffusion Model Researcher','specialty'=>'Denoising diffusion for speech, score matching, SDE sampling','tier'=>'Junior','station'=>'Diffusion Model Bay'],
        ['name'=>'Dr. Whisper Decode','division'=>'tts_engineers','role'=>'ASR Integration Engineer','specialty'=>'Whisper model fine-tuning, real-time transcription, STT-TTS pipeline','tier'=>'Junior','station'=>'ASR Integration Lab'],

        // ── Division 5: Intelligence Analysts (10) — Data & Briefings ──
        ['name'=>'Analyst Alpha Prime','division'=>'intelligence_analysts','role'=>'Chief Intelligence Analyst','specialty'=>'Global data aggregation, threat assessment, strategic briefing compilation','tier'=>'Director','station'=>'Intel Command Center'],
        ['name'=>'Analyst Echo Signal','division'=>'intelligence_analysts','role'=>'SIGINT Specialist','specialty'=>'Signal interception analysis, frequency monitoring, encrypted traffic patterns','tier'=>'Senior','station'=>'Signal Processing Desk'],
        ['name'=>'Analyst Cipher Key','division'=>'intelligence_analysts','role'=>'Cryptanalysis Lead','specialty'=>'Code breaking, pattern recognition, anomaly detection in encrypted data','tier'=>'Senior','station'=>'Cryptanalysis Terminal'],
        ['name'=>'Analyst Nova Trend','division'=>'intelligence_analysts','role'=>'Trend Forecaster','specialty'=>'Global trend analysis, technology forecasting, geopolitical projection','tier'=>'Senior','station'=>'Forecasting Station'],
        ['name'=>'Analyst Data Stream','division'=>'intelligence_analysts','role'=>'Real-Time Data Processor','specialty'=>'Live feed monitoring, event correlation, multi-source fusion','tier'=>'Mid','station'=>'Live Feed Wall'],
        ['name'=>'Analyst Shadow Map','division'=>'intelligence_analysts','role'=>'Geospatial Intelligence','specialty'=>'Satellite imagery analysis, terrain mapping, facility identification','tier'=>'Mid','station'=>'GIS Mapping Station'],
        ['name'=>'Analyst Raven Profile','division'=>'intelligence_analysts','role'=>'Behavioral Profiler','specialty'=>'Psychological analysis, entity profiling, communication pattern analysis','tier'=>'Mid','station'=>'Profiling Desk'],
        ['name'=>'Analyst Quartz Report','division'=>'intelligence_analysts','role'=>'Report Generator','specialty'=>'Executive briefing creation, classified document formatting, data visualization','tier'=>'Junior','station'=>'Report Generation Terminal'],
        ['name'=>'Analyst Onyx Archive','division'=>'intelligence_analysts','role'=>'Archive Manager','specialty'=>'Historical data retrieval, cross-reference analysis, knowledge base curation','tier'=>'Junior','station'=>'Archive Access Terminal'],
        ['name'=>'Analyst Prism Filter','division'=>'intelligence_analysts','role'=>'Information Filter','specialty'=>'Source verification, disinformation detection, credibility scoring','tier'=>'Junior','station'=>'Verification Station'],

        // ── Division 6: Event Coordinators (10) — Private Ceremonies ──
        ['name'=>'Coord. Gala Prime','division'=>'event_coordinators','role'=>'Chief Event Director','specialty'=>'Ultra-classified ceremonies, VIP protocols, venue orchestration','tier'=>'Director','station'=>'Event Command Suite'],
        ['name'=>'Coord. Velvet Stage','division'=>'event_coordinators','role'=>'Stage Director','specialty'=>'Ceremony choreography, lighting design, theatrical production','tier'=>'Senior','station'=>'Stage Design Studio'],
        ['name'=>'Coord. Crystal Sound','division'=>'event_coordinators','role'=>'Audio-Visual Director','specialty'=>'Immersive sound systems, holographic displays, live streaming infrastructure','tier'=>'Senior','station'=>'AV Control Room'],
        ['name'=>'Coord. Ivory Protocol','division'=>'event_coordinators','role'=>'Protocol Officer','specialty'=>'Diplomatic protocols, seating arrangements, security clearance verification','tier'=>'Senior','station'=>'Protocol Office'],
        ['name'=>'Coord. Amber Guest','division'=>'event_coordinators','role'=>'Guest Relations Manager','specialty'=>'Invitation management, RSVP tracking, VIP accommodation','tier'=>'Mid','station'=>'Guest Relations Desk'],
        ['name'=>'Coord. Silk Catering','division'=>'event_coordinators','role'=>'Catering Director','specialty'=>'Michelin-star menu planning, dietary requirements, wine pairing','tier'=>'Mid','station'=>'Catering Suite'],
        ['name'=>'Coord. Orchid Decor','division'=>'event_coordinators','role'=>'Decor & Ambiance Designer','specialty'=>'Floral arrangements, thematic decoration, atmosphere engineering','tier'=>'Mid','station'=>'Design Studio'],
        ['name'=>'Coord. Onyx Security','division'=>'event_coordinators','role'=>'Event Security Chief','specialty'=>'Classified venue security, guest screening, counter-surveillance','tier'=>'Junior','station'=>'Security Operations'],
        ['name'=>'Coord. Pearl Transport','division'=>'event_coordinators','role'=>'Transport Coordinator','specialty'=>'VIP convoy routes, aircraft scheduling, secure transportation','tier'=>'Junior','station'=>'Transport Hub'],
        ['name'=>'Coord. Jade Archive','division'=>'event_coordinators','role'=>'Event Historian','specialty'=>'Ceremony documentation, achievement recording, legacy archive management','tier'=>'Junior','station'=>'Archive Suite'],

        // ── Division 7: SDK & Viewer Developers (10) — Monitoring Tools ──
        ['name'=>'Dev. Render Prime','division'=>'sdk_developers','role'=>'Chief SDK Architect','specialty'=>'Real-time 3D rendering, WebGL dashboards, SDK framework design','tier'=>'Director','station'=>'SDK Command Center'],
        ['name'=>'Dev. WebGL Forge','division'=>'sdk_developers','role'=>'3D Visualization Lead','specialty'=>'Three.js, Babylon.js, WebGPU, real-time 3D craft viewers','tier'=>'Senior','station'=>'3D Render Farm'],
        ['name'=>'Dev. React Stream','division'=>'sdk_developers','role'=>'Frontend Architect','specialty'=>'Real-time dashboards, WebSocket data feeds, reactive UI systems','tier'=>'Senior','station'=>'Frontend Studio'],
        ['name'=>'Dev. API Gateway','division'=>'sdk_developers','role'=>'API Engineer','specialty'=>'REST/GraphQL APIs, real-time data endpoints, SDK client libraries','tier'=>'Senior','station'=>'API Engineering Bay'],
        ['name'=>'Dev. Canvas Paint','division'=>'sdk_developers','role'=>'Data Visualization Specialist','specialty'=>'D3.js, Chart.js, telemetry dashboards, flight path rendering','tier'=>'Mid','station'=>'Visualization Lab'],
        ['name'=>'Dev. Socket Live','division'=>'sdk_developers','role'=>'Real-Time Comms Engineer','specialty'=>'WebSocket servers, event streaming, live telemetry protocols','tier'=>'Mid','station'=>'Live Feed Engineering'],
        ['name'=>'Dev. Embed Widget','division'=>'sdk_developers','role'=>'Widget Developer','specialty'=>'Embeddable components, iframe security, cross-origin communication','tier'=>'Mid','station'=>'Widget Workshop'],
        ['name'=>'Dev. Mobile View','division'=>'sdk_developers','role'=>'Mobile SDK Developer','specialty'=>'React Native, Flutter, mobile telemetry viewers, push notifications','tier'=>'Junior','station'=>'Mobile Dev Bay'],
        ['name'=>'Dev. VR Immerse','division'=>'sdk_developers','role'=>'VR Experience Developer','specialty'=>'A-Frame, WebXR, VR craft tours, immersive monitoring environments','tier'=>'Junior','station'=>'VR Development Lab'],
        ['name'=>'Dev. Test Harness','division'=>'sdk_developers','role'=>'QA & Testing Lead','specialty'=>'SDK test suites, integration testing, performance benchmarking','tier'=>'Junior','station'=>'QA Lab'],

        // ── Division 8: Equipment Specialists (10) — Procurement & Maintenance ──
        ['name'=>'Spec. Quantum Core','division'=>'equipment_specialists','role'=>'Quantum Equipment Director','specialty'=>'Quantum computer procurement, qubit hardware selection, cryostat maintenance','tier'=>'Director','station'=>'Quantum Hardware Bay'],
        ['name'=>'Spec. Horizon Scope','division'=>'equipment_specialists','role'=>'Telescope & Optics Manager','specialty'=>'Adaptive optics, interferometry equipment, spectroscopic instruments','tier'=>'Senior','station'=>'Optics Clean Room'],
        ['name'=>'Spec. Volt Supply','division'=>'equipment_specialists','role'=>'Power Infrastructure Lead','specialty'=>'UPS systems, clean power delivery, electromagnetic shielding','tier'=>'Senior','station'=>'Power Systems Room'],
        ['name'=>'Spec. Nano Fabricate','division'=>'equipment_specialists','role'=>'Nanofabrication Equipment Lead','specialty'=>'Electron beam lithography, atomic layer deposition, focused ion beam','tier'=>'Senior','station'=>'Nanofab Clean Room'],
        ['name'=>'Spec. Bio Chamber','division'=>'equipment_specialists','role'=>'Biotech Equipment Manager','specialty'=>'Gene sequencers, CRISPR delivery systems, bioreactor management','tier'=>'Mid','station'=>'Biotech Equipment Bay'],
        ['name'=>'Spec. Forge Alloy','division'=>'equipment_specialists','role'=>'Materials Processing Lead','specialty'=>'Arc melters, sintering furnaces, CVD reactors, crystal pullers','tier'=>'Mid','station'=>'Materials Forge'],
        ['name'=>'Spec. Shield Rad','division'=>'equipment_specialists','role'=>'Radiation Safety Officer','specialty'=>'Dosimetry, shielding design, radioactive material handling','tier'=>'Mid','station'=>'Radiation Lab'],
        ['name'=>'Spec. Atmos Control','division'=>'equipment_specialists','role'=>'Environmental Systems Manager','specialty'=>'Inert gas systems, humidity control, clean room HVAC','tier'=>'Junior','station'=>'Environmental Control Center'],
        ['name'=>'Spec. Cal Standard','division'=>'equipment_specialists','role'=>'Calibration Specialist','specialty'=>'NIST traceable standards, metrology, precision instrument calibration','tier'=>'Junior','station'=>'Calibration Lab'],
        ['name'=>'Spec. Repair Bay','division'=>'equipment_specialists','role'=>'Maintenance Technician','specialty'=>'Preventive maintenance, equipment repair, spare parts inventory','tier'=>'Junior','station'=>'Maintenance Bay'],

        // ── Division 9: Flight Test Pilots & Simulation (10) ──
        ['name'=>'Pilot Ace Horizon','division'=>'flight_test','role'=>'Chief Test Pilot','specialty'=>'Experimental craft piloting, extreme maneuver testing, performance envelope expansion','tier'=>'Director','station'=>'Pilot Briefing Room'],
        ['name'=>'Pilot Nova Climb','division'=>'flight_test','role'=>'High-Altitude Test Pilot','specialty'=>'Stratospheric testing, pressure suit operations, hypoxia management','tier'=>'Senior','station'=>'High-Altitude Prep Bay'],
        ['name'=>'Pilot Storm Vector','division'=>'flight_test','role'=>'Supersonic Test Pilot','specialty'=>'Mach transition testing, shockwave management, sonic boom profiling','tier'=>'Senior','station'=>'Supersonic Prep Bay'],
        ['name'=>'Pilot Echo Drone','division'=>'flight_test','role'=>'UAV Test Operator','specialty'=>'Autonomous flight testing, swarm coordination, remote sensor operation','tier'=>'Senior','station'=>'Drone Control Center'],
        ['name'=>'Pilot Zero Gravity','division'=>'flight_test','role'=>'Zero-G Test Specialist','specialty'=>'Microgravity testing, parabolic flight, orbital insertion maneuvers','tier'=>'Mid','station'=>'Zero-G Prep Chamber'],
        ['name'=>'Pilot Luna Return','division'=>'flight_test','role'=>'Re-entry Test Pilot','specialty'=>'Atmospheric re-entry profiles, heat shield validation, landing precision','tier'=>'Mid','station'=>'Re-entry Training Pod'],
        ['name'=>'Sim. Matrix Flight','division'=>'flight_test','role'=>'Chief Simulator Operator','specialty'=>'Full-motion flight simulation, scenario programming, failure injection','tier'=>'Mid','station'=>'Flight Simulator Alpha'],
        ['name'=>'Sim. Data Record','division'=>'flight_test','role'=>'Flight Data Analyst','specialty'=>'Telemetry recording, flight data analysis, anomaly detection','tier'=>'Junior','station'=>'Telemetry Station'],
        ['name'=>'Sim. Weather Wind','division'=>'flight_test','role'=>'Meteorological Advisor','specialty'=>'Test-day weather assessment, wind modeling, turbulence prediction','tier'=>'Junior','station'=>'Weather Station'],
        ['name'=>'Sim. Safety Net','division'=>'flight_test','role'=>'Range Safety Officer','specialty'=>'Test range management, abort system verification, emergency protocols','tier'=>'Junior','station'=>'Range Control Tower'],

        // ── Division 10: Archive Curators & Knowledge Management (10) ──
        ['name'=>'Curator Prime Vault','division'=>'archive_curators','role'=>'Chief Archivist','specialty'=>'Knowledge taxonomy, classification systems, archival preservation','tier'=>'Director','station'=>'Grand Archive'],
        ['name'=>'Curator Codex Ancient','division'=>'archive_curators','role'=>'Historical Documents Lead','specialty'=>'Ancient text analysis, manuscript preservation, historical cross-referencing','tier'=>'Senior','station'=>'Ancient Documents Room'],
        ['name'=>'Curator Digital Forge','division'=>'archive_curators','role'=>'Digital Archive Manager','specialty'=>'Digital preservation, format migration, metadata standards, OAIS compliance','tier'=>'Senior','station'=>'Digital Vault'],
        ['name'=>'Curator Patent Index','division'=>'archive_curators','role'=>'Patent & IP Researcher','specialty'=>'Patent landscape analysis, prior art searches, invention documentation','tier'=>'Senior','station'=>'Patent Library'],
        ['name'=>'Curator Schema Map','division'=>'archive_curators','role'=>'Knowledge Graph Architect','specialty'=>'Ontology design, knowledge graph construction, semantic linking','tier'=>'Mid','station'=>'Knowledge Graph Terminal'],
        ['name'=>'Curator Timeline Weave','division'=>'archive_curators','role'=>'Chronology Specialist','specialty'=>'Timeline construction, event correlation, historical context mapping','tier'=>'Mid','station'=>'Timeline Room'],
        ['name'=>'Curator Source Verify','division'=>'archive_curators','role'=>'Fact Verification Lead','specialty'=>'Source authentication, citation verification, accuracy auditing','tier'=>'Mid','station'=>'Verification Desk'],
        ['name'=>'Curator Translate Bridge','division'=>'archive_curators','role'=>'Multilingual Specialist','specialty'=>'Scientific translation, terminology standardization, cross-language analysis','tier'=>'Junior','station'=>'Translation Suite'],
        ['name'=>'Curator Bio Record','division'=>'archive_curators','role'=>'Biographical Researcher','specialty'=>'Scientist biographies, contribution mapping, legacy documentation','tier'=>'Junior','station'=>'Biography Archive'],
        ['name'=>'Curator Index Alpha','division'=>'archive_curators','role'=>'Indexing Specialist','specialty'=>'Full-text indexing, cross-reference systems, search optimization','tier'=>'Junior','station'=>'Index Terminal'],
    ];
}

// ═══════════════════════════════════════════════════════════════════
// EQUIPMENT DATABASE — Everything the Scientists Need
// ═══════════════════════════════════════════════════════════════════
function getEquipmentDatabase() {
    return [
        // ── Boards & Visualization ──
        ['id'=>'EQ-001','name'=>'Infinite Chalkboard Alpha','category'=>'boards','desc'=>'12×4ft slate chalkboard with colored chalk, magnetic equation holders, proof progression tracking','location'=>'Chalkboard Room Alpha','status'=>'active'],
        ['id'=>'EQ-002','name'=>'Infinite Chalkboard Beta','category'=>'boards','desc'=>'Dual-sided rotating chalkboard (8×4ft each side), integrated document camera for capture','location'=>'Chalkboard Room Beta','status'=>'active'],
        ['id'=>'EQ-003','name'=>'Smart Whiteboard Array','category'=>'boards','desc'=>'6 interconnected 75" digital whiteboards with real-time sync, handwriting-to-LaTeX conversion','location'=>'Whiteboard Lab 1','status'=>'active'],
        ['id'=>'EQ-004','name'=>'Glass Whiteboard Wall','category'=>'boards','desc'=>'Floor-to-ceiling tempered glass writing surface with neon markers, backlit for presentations','location'=>'Whiteboard Lab 2','status'=>'active'],
        ['id'=>'EQ-005','name'=>'Holographic Display Table','category'=>'boards','desc'=>'4K volumetric holographic display, 360° viewable, gesture-controlled 3D model manipulation','location'=>'Holographic Lab','status'=>'active'],
        ['id'=>'EQ-006','name'=>'The Infinite Board','category'=>'boards','desc'=>'Curved 180° projection wall with multi-touch, infinite zoom canvas for mathematical proofs','location'=>'The Infinite Board Room','status'=>'active'],

        // ── Computing & Processing ──
        ['id'=>'EQ-010','name'=>'Quantum Computer — 1024 Qubits','category'=>'computing','desc'=>'Superconducting quantum processor, dilution refrigerator at 15mK, error-corrected logical qubits','location'=>'Quantum Hardware Bay','status'=>'active'],
        ['id'=>'EQ-011','name'=>'HPC Cluster — 256 NVIDIA H100','category'=>'computing','desc'=>'256x H100 GPUs, NVLink mesh, 2PB NVMe storage, InfiniBand HDR200 interconnect','location'=>'Computing Center','status'=>'active'],
        ['id'=>'EQ-012','name'=>'Neural Processing Array','category'=>'computing','desc'=>'Custom TPU cluster for TTS model training, 512 TOPS, dedicated prosody pipeline hardware','location'=>'Neural Voice Lab','status'=>'active'],
        ['id'=>'EQ-013','name'=>'FPGA Development Rack','category'=>'computing','desc'=>'32x Xilinx VU13P FPGAs for real-time signal processing and custom hardware prototyping','location'=>'Electronics Workshop','status'=>'active'],

        // ── Physics Equipment ──
        ['id'=>'EQ-020','name'=>'Mini Particle Accelerator','category'=>'physics','desc'=>'Desktop-scale linear accelerator, 10 MeV electrons, target chamber with scintillation detector','location'=>'Particle Detection Bay','status'=>'active'],
        ['id'=>'EQ-021','name'=>'Casimir Force Apparatus','category'=>'physics','desc'=>'Sub-micron gap plate assembly with piezo actuators, femtoNewton force measurement','location'=>'Vacuum Chamber Bay','status'=>'active'],
        ['id'=>'EQ-022','name'=>'Superconducting Magnet Array','category'=>'physics','desc'=>'12 Tesla solenoid with variable geometry, gradient coils, NMR-grade homogeneity','location'=>'Cryogenics Lab','status'=>'active'],
        ['id'=>'EQ-023','name'=>'Gravitational Wave Antenna','category'=>'physics','desc'=>'Tabletop laser interferometer, 10⁻¹⁸ m sensitivity, seismic isolation platform','location'=>'Precision Measurement Lab','status'=>'active'],
        ['id'=>'EQ-024','name'=>'Dark Matter Detector Prototype','category'=>'physics','desc'=>'Liquid xenon TPC, underground-grade shielding, single-photon PMT array','location'=>'Deep Lab','status'=>'active'],

        // ── Chemistry & Materials ──
        ['id'=>'EQ-030','name'=>'Mass Spectrometer Array','category'=>'chemistry','desc'=>'Triple-quad MS with MALDI source, ppm accuracy, automated sample injection','location'=>'Spectrometry Bay','status'=>'active'],
        ['id'=>'EQ-031','name'=>'Electron Beam Lithography','category'=>'chemistry','desc'=>'Sub-10nm patterning, 100kV acceleration, automated alignment for nanodevice fabrication','location'=>'Nanofab Clean Room','status'=>'active'],
        ['id'=>'EQ-032','name'=>'Atomic Layer Deposition','category'=>'chemistry','desc'=>'Plasma-enhanced ALD, 200mm wafer capacity, Ångström-level thickness control','location'=>'Nanofab Clean Room','status'=>'active'],
        ['id'=>'EQ-033','name'=>'Crystal Growth Furnace','category'=>'chemistry','desc'=>'Czochralski puller with optical diameter control, 1800°C max, argon atmosphere','location'=>'Materials Forge','status'=>'active'],

        // ── Craft & Propulsion Testing ──
        ['id'=>'EQ-040','name'=>'Hypersonic Wind Tunnel','category'=>'craft','desc'=>'Mach 0.1–12 variable speed, Schlieren imaging, force/moment balance, 0.5m test section','location'=>'Wind Tunnel Control','status'=>'active'],
        ['id'=>'EQ-041','name'=>'Electromagnetic Drive Test Stand','category'=>'craft','desc'=>'Thrust measurement at nanoNewton sensitivity, vibration isolation, vacuum-rated','location'=>'Propulsion Lab','status'=>'active'],
        ['id'=>'EQ-042','name'=>'Radar Cross-Section Chamber','category'=>'craft','desc'=>'Anechoic chamber with multi-frequency radar emitters, 360° turntable, RCS mapping','location'=>'Stealth Chamber','status'=>'active'],
        ['id'=>'EQ-043','name'=>'Full-Motion Flight Simulator','category'=>'craft','desc'=>'6-DOF motion platform, 220° visual display, full craft systems emulation, VR headset option','location'=>'Flight Simulator Alpha','status'=>'active'],
        ['id'=>'EQ-044','name'=>'Craft Assembly Robotic Arms','category'=>'craft','desc'=>'4x precision robotic arms, 0.01mm accuracy, AI-guided assembly, heavy lift capability','location'=>'Assembly Bay Alpha','status'=>'active'],

        // ── Voice & Audio ──
        ['id'=>'EQ-050','name'=>'Anechoic Recording Chamber','category'=>'audio','desc'=>'Fully anechoic room, -22dB noise floor, calibrated microphone array, vibration-isolated floor','location'=>'Neural Voice Lab','status'=>'active'],
        ['id'=>'EQ-051','name'=>'Neural Vocoder Test Rig','category'=>'audio','desc'=>'Real-time audio playback + analysis, spectral visualization, A/B comparison, MOS scoring','location'=>'Vocoder Testing Lab','status'=>'active'],
        ['id'=>'EQ-052','name'=>'Speaker Embedding Capture Array','category'=>'audio','desc'=>'8-mic circular array, 48kHz/24bit capture, automatic noise gate, speaker isolation booth','location'=>'Voice Cloning Chamber','status'=>'active'],

        // ── Biotech ──
        ['id'=>'EQ-060','name'=>'Gene Sequencer Array','category'=>'biotech','desc'=>'Nanopore + Illumina parallel sequencing, real-time basecalling, whole-genome in 4 hours','location'=>'Biotech Equipment Bay','status'=>'active'],
        ['id'=>'EQ-061','name'=>'CRISPR Delivery System','category'=>'biotech','desc'=>'Electroporation + lipid nanoparticle system, multiple cell-type protocols, guide RNA library','location'=>'Biotech Equipment Bay','status'=>'active'],

        // ── Latest Technology — Bleeding Edge ──
        ['id'=>'EQ-070','name'=>'Photonic Quantum Processor','category'=>'bleeding_edge','desc'=>'Squeezed-light photonic processor, room-temperature operation, 1M modes, Xanadu-class','location'=>'Quantum Hardware Bay','status'=>'prototype'],
        ['id'=>'EQ-071','name'=>'Neuromorphic Chip Array','category'=>'bleeding_edge','desc'=>'Intel Loihi 3 cluster, 1M artificial neurons, spike-based processing, 100x energy efficiency','location'=>'Neural Voice Lab','status'=>'prototype'],
        ['id'=>'EQ-072','name'=>'Room-Temp Superconductor Test Chamber','category'=>'bleeding_edge','desc'=>'High-pressure diamond anvil + modified LK-99 samples, resistance and Meissner measurement','location'=>'Cryogenics Lab','status'=>'experimental'],
        ['id'=>'EQ-073','name'=>'Metamaterial Invisibility Cloak','category'=>'bleeding_edge','desc'=>'Microwave-band carpet cloak, 10cm concealment volume, active tuning elements','location'=>'Stealth Chamber','status'=>'experimental'],
        ['id'=>'EQ-074','name'=>'Gravitational Anomaly Detector','category'=>'bleeding_edge','desc'=>'Atom interferometry gravimeter, 10⁻¹² g sensitivity, anomalous acceleration search','location'=>'Deep Lab','status'=>'experimental'],
    ];
}

// ═══════════════════════════════════════════════════════════════════
// NEURAL TTS ENGINE — Beyond Coqui: Prosody Modeling Architecture
// ═══════════════════════════════════════════════════════════════════
function getNeuralTTSArchitecture() {
    return [
        'engine_name' => 'NEXUS Voice Engine v1.0',
        'classification' => 'ULTRA SECRET',
        'description' => 'Next-generation neural TTS with prosody prediction, style embeddings, and streaming inference — surpassing ElevenLabs architecture',

        'pipeline' => [
            ['stage'=>1,'name'=>'Text Analysis','module'=>'SemanticEncoder','desc'=>'Transformer encoder processes input text for deep semantic understanding — context, intent, emotion implied by word choice','tech'=>'BERT-large backbone, 24 layers, 1024 hidden, trained on scientific + conversational corpus','output'=>'Semantic embeddings (1024-dim per token)'],
            ['stage'=>2,'name'=>'Prosody Prediction','module'=>'ProsodyPredictor','desc'=>'Predicts pitch contours (F0), energy curves, duration, and pause placement for each phoneme — THIS is what makes speech sound human','tech'=>'Variational Autoencoder (VAE) + Flow matching, trained on 100k hours of expressive speech','output'=>'Prosody vectors: pitch (Hz), energy (dB), duration (ms), pause probability'],
            ['stage'=>3,'name'=>'Style Embedding','module'=>'StyleEncoder','desc'=>'Encodes emotional style into a latent vector that controls the entire generation — whisper, excitement, authority, warmth, sorrow','tech'=>'Reference encoder with multi-head attention, GST (Global Style Tokens) with 128 style bases','output'=>'Style vector (256-dim): controls emotion, speaking rate, emphasis patterns'],
            ['stage'=>4,'name'=>'Speaker Embedding','module'=>'SpeakerEncoder','desc'=>'Captures unique voice characteristics — accent, vocal timber, pitch range, breathiness, resonance','tech'=>'GE2E (Generalized End-to-End) speaker verification model, 3-second voice sample minimum','output'=>'Speaker embedding (256-dim): unique voice DNA of any speaker'],
            ['stage'=>5,'name'=>'Acoustic Generation','module'=>'AcousticDecoder','desc'=>'Combines semantic + prosody + style + speaker into mel-spectrogram frames — the visual representation of sound','tech'=>'Flow-matching decoder with CFG (Classifier-Free Guidance), 80-mel-bin spectrograms at 22050 Hz','output'=>'Mel-spectrogram (80 bins × T frames)'],
            ['stage'=>6,'name'=>'Neural Vocoder','module'=>'HiFiGAN_v2','desc'=>'Converts mel-spectrogram to raw audio waveform with near-perfect quality — no robotic artifacts','tech'=>'Multi-period + multi-scale discriminator, 48kHz output, 0.1 RTF (10x real-time on GPU)','output'=>'Raw audio waveform (48kHz, 16-bit PCM)'],
            ['stage'=>7,'name'=>'Streaming Output','module'=>'ChunkedStreamer','desc'=>'Enables real-time playback by generating audio in 200ms chunks — speech starts before sentence finishes','tech'=>'Sliding context window, overlap-add synthesis, WebSocket delivery, <100ms first-chunk latency','output'=>'Audio stream (200ms chunks via WebSocket)'],
        ],

        'style_tokens' => [
            ['token'=>'[neutral]','desc'=>'Standard conversational voice','pitch_mod'=>1.0,'energy_mod'=>1.0,'rate_mod'=>1.0],
            ['token'=>'[whisper]','desc'=>'Breathy, quiet, intimate delivery','pitch_mod'=>0.8,'energy_mod'=>0.3,'rate_mod'=>0.9],
            ['token'=>'[excited]','desc'=>'High energy, faster pace, wider pitch range','pitch_mod'=>1.3,'energy_mod'=>1.5,'rate_mod'=>1.2],
            ['token'=>'[authoritative]','desc'=>'Deep, commanding, measured pace','pitch_mod'=>0.85,'energy_mod'=>1.3,'rate_mod'=>0.85],
            ['token'=>'[warm]','desc'=>'Friendly, approachable, slight smile in voice','pitch_mod'=>1.1,'energy_mod'=>0.9,'rate_mod'=>0.95],
            ['token'=>'[somber]','desc'=>'Low energy, slower pace, reflective','pitch_mod'=>0.75,'energy_mod'=>0.6,'rate_mod'=>0.8],
            ['token'=>'[scientific]','desc'=>'Precise, measured, clear enunciation, technical','pitch_mod'=>0.95,'energy_mod'=>1.0,'rate_mod'=>0.9],
            ['token'=>'[dramatic]','desc'=>'Theatrical, wide dynamic range, pauses for effect','pitch_mod'=>1.2,'energy_mod'=>1.4,'rate_mod'=>0.85],
            ['token'=>'[laughing]','desc'=>'Speech interspersed with laughter patterns','pitch_mod'=>1.25,'energy_mod'=>1.3,'rate_mod'=>1.1],
            ['token'=>'[urgent]','desc'=>'Fast, clipped, heightened tension','pitch_mod'=>1.15,'energy_mod'=>1.4,'rate_mod'=>1.3],
        ],

        'models_supported' => [
            ['name'=>'VITS','type'=>'End-to-End','desc'=>'Variational Inference TTS — single-stage, fast, good quality','status'=>'integrated'],
            ['name'=>'StyleTTS 2','type'=>'Style-based','desc'=>'Diffusion-based style modeling, state-of-art naturalness','status'=>'integrated'],
            ['name'=>'Bark','type'=>'GPT-style','desc'=>'Transformer language model approach, handles non-verbal sounds','status'=>'integrated'],
            ['name'=>'Coqui TTS','type'=>'Open-source','desc'=>'Current production engine — being enhanced with prosody layer','status'=>'production'],
            ['name'=>'XTTS v2','type'=>'Cross-lingual','desc'=>'Cross-language voice cloning, 17 languages, zero-shot','status'=>'integrated'],
            ['name'=>'F5-TTS','type'=>'Flow-matching','desc'=>'Latest flow-matching architecture, ultra-fast inference','status'=>'experimental'],
            ['name'=>'NEXUS Custom','type'=>'Hybrid','desc'=>'Our proprietary pipeline combining best of all architectures','status'=>'in_development'],
        ],

        'vs_coqui_upgrade' => [
            'Coqui: phoneme-level prosody → NEXUS: sentence-level semantic prosody prediction',
            'Coqui: basic speaker cloning → NEXUS: style-disentangled multi-speaker with emotion control',
            'Coqui: full sentence generation → NEXUS: chunked streaming (200ms first-chunk latency)',
            'Coqui: single style per utterance → NEXUS: mid-sentence style switching via inline tokens',
            'Coqui: WaveRNN vocoder → NEXUS: HiFi-GAN v2 (48kHz, near-transparent quality)',
            'Coqui: CPU inference OK → NEXUS: GPU-optimized with ONNX runtime for 10x speed',
        ],

        'code_example' => "# NEXUS TTS Engine — Python Example\nfrom nexus_tts import NexusEngine, StyleToken\n\nengine = NexusEngine(model='nexus-custom-v1')\n\n# Clone a voice from a 3-second sample\nspeaker = engine.clone_voice('commander_danny.wav')\n\n# Generate with prosody control\naudio = engine.synthesize(\n    text='[authoritative] Commander, the craft test results are in. '\n         '[excited] Thrust exceeded predictions by 340 percent!',\n    speaker=speaker,\n    style=StyleToken.SCIENTIFIC,\n    streaming=True  # 200ms chunks\n)\n\n# Stream to output\nfor chunk in audio:\n    play_audio(chunk)",
    ];
}

// ═══════════════════════════════════════════════════════════════════
// CRAFT PROGRAM — Design, Build, Test, Fly
// ═══════════════════════════════════════════════════════════════════
function getCraftDesigns() {
    return [
        ['id'=>'CRAFT-001','codename'=>'Vortex Alpha','class'=>'Scout','status'=>'design_complete','desc'=>'Compact disc-shaped scout craft, 3m diameter, electromagnetic lift, optical camouflage','propulsion'=>'Electromagnetic drive (Biefeld-Brown + ZPE coupling)','max_speed'=>'Mach 3.2','range'=>'2,400 km','crew'=>0,'features'=>['360° sensor array','Active stealth','Autonomous navigation','Quantum-encrypted telemetry']],
        ['id'=>'CRAFT-002','codename'=>'Thunderbolt','class'=>'Interceptor','status'=>'build_phase','desc'=>'Delta-wing interceptor, 8m wingspan, hybrid jet-electromagnetic propulsion, manned','propulsion'=>'Scramjet + EM drive boost','max_speed'=>'Mach 8.5','range'=>'12,000 km','crew'=>1,'features'=>['Pilot neural interface','G-force compensation','Metamaterial hull','AI co-pilot']],
        ['id'=>'CRAFT-003','codename'=>'Leviathan','class'=>'Heavy Transport','status'=>'testing','desc'=>'Large triangular craft, 45m span, anti-gravity lift panels, cargo bay for 50 tons','propulsion'=>'Anti-gravity array (Podkletnov-type) + plasma thrusters','max_speed'=>'Mach 1.5','range'=>'Global','crew'=>4,'features'=>['Cargo crane system','Stealth mode','VTOL capability','Self-repair hull']],
        ['id'=>'CRAFT-004','codename'=>'Wraith','class'=>'Stealth Recon','status'=>'flight_testing','desc'=>'Ultra-low observable flying wing, 12m span, passive sensors only, near-silent operation','propulsion'=>'Electric ducted fans + EM lift assist','max_speed'=>'Mach 0.9','range'=>'8,000 km','crew'=>0,'features'=>['Infrared suppression','Acoustic dampening','AI terrain following','48hr endurance']],
        ['id'=>'CRAFT-005','codename'=>'Horizon','class'=>'Orbital','status'=>'design_phase','desc'=>'SSTO (Single Stage to Orbit) craft, 20m diameter, seats 6, reusable heat shield','propulsion'=>'Warp-assisted orbital insertion (Alcubierre micro-metric)','max_speed'=>'Orbital velocity','range'=>'LEO to Lunar','crew'=>6,'features'=>['Artificial gravity ring','Radiation shielding','Docking system','Emergency capsule']],
        ['id'=>'CRAFT-006','codename'=>'Phantom Wave','class'=>'Submarine-Air Hybrid','status'=>'concept','desc'=>'Trans-medium vehicle — operates underwater, surface, and airborne seamlessly','propulsion'=>'Supercavitation + EM drive + hydro-jets','max_speed'=>'200 knots sub / Mach 2 air','range'=>'6,000 km','crew'=>2,'features'=>['Pressure hull rated 1000m depth','Seamless transition','Sonar stealth','Amphibious landing']],
    ];
}

// ═══════════════════════════════════════════════════════════════════
// AGENDA INTEL BRIEFINGS — Batman's Cave → Commander's Desk
// ═══════════════════════════════════════════════════════════════════
function getAgendaBriefings() {
    return [
        ['id'=>'BRIEF-001','title'=>'DAILY INTELLIGENCE SUMMARY','priority'=>'critical','type'=>'daily','schedule'=>'Every day at 0600','content'=>'Overnight research breakthroughs, craft test results, lab status, global tech developments, threat assessments','format'=>'Executive summary with action items','delivery'=>'Auto-spawn in Commander agenda + voice briefing option'],
        ['id'=>'BRIEF-002','title'=>'CRAFT STATUS REPORT','priority'=>'high','type'=>'weekly','schedule'=>'Every Monday at 0800','content'=>'All craft design progress, build milestones, test flight schedules, performance data, anomalies detected','format'=>'Visual dashboard + detailed PDF','delivery'=>'Commander agenda + SDK craft viewer link'],
        ['id'=>'BRIEF-003','title'=>'RESEARCH BREAKTHROUGH ALERT','priority'=>'critical','type'=>'event','schedule'=>'Immediate — when discovery occurs','content'=>'New discovery details, implications, researcher notes, supporting evidence, next steps','format'=>'Flash briefing with chalkboard snapshots','delivery'=>'Push notification + agenda priority item'],
        ['id'=>'BRIEF-004','title'=>'TTS ENGINE PROGRESS','priority'=>'medium','type'=>'weekly','schedule'=>'Every Wednesday at 1000','content'=>'Model training metrics, MOS scores, new voices cloned, style token accuracy, inference speed benchmarks','format'=>'Technical dashboard with audio samples','delivery'=>'Commander agenda with playable audio clips'],
        ['id'=>'BRIEF-005','title'=>'PRIVATE EVENT PLANNING','priority'=>'high','type'=>'as_needed','schedule'=>'30 days before each ceremony','content'=>'Event timeline, guest list updates, venue preparation status, catering, AV setup, security protocols','format'=>'Event planner view with checklists','delivery'=>'Commander agenda + dedicated event page'],
        ['id'=>'BRIEF-006','title'=>'EQUIPMENT & PROCUREMENT','priority'=>'medium','type'=>'monthly','schedule'=>'1st of each month','content'=>'New equipment arrivals, maintenance schedules, procurement pipeline, budget status, wish list from scientists','format'=>'Inventory report with photos','delivery'=>'Commander agenda item'],
        ['id'=>'BRIEF-007','title'=>'PANTHEON LUMINARY REPORTS','priority'=>'high','type'=>'bi_weekly','schedule'=>'Every other Friday at 1400','content'=>'Research progress from all 100 luminaries, thesis updates, breakthrough candidates, collaboration notes','format'=>'Department-by-department summary','delivery'=>'Commander agenda + Pantheon dashboard sync'],
        ['id'=>'BRIEF-008','title'=>'GENESIS QUESTION PROGRESS','priority'=>'medium','type'=>'monthly','schedule'=>'15th of each month','content'=>'Progress on the 100 ultimate questions, new hypotheses, evidence compilation, agent deliberations','format'=>'Question-by-question status board','delivery'=>'Commander agenda + Genesis dashboard sync'],
        ['id'=>'BRIEF-009','title'=>'SECURITY & VEIL STATUS','priority'=>'critical','type'=>'daily','schedule'=>'Every day at 2200','content'=>'Veil Protocol integrity, encryption status, access attempts, anomaly detection, crypto key rotation status','format'=>'Security dashboard with threat matrix','delivery'=>'Commander agenda (classified section)'],
        ['id'=>'BRIEF-010','title'=>'COMMANDER-ALFRED PRIVATE SESSION','priority'=>'critical','type'=>'weekly','schedule'=>'Every Sunday at 2100','content'=>'One-on-one briefing: week summary, strategic decisions needed, Alfred recommendations, philosophical discussion topics','format'=>'Conversational brief — just you and Alfred','delivery'=>'Commander agenda (private — no delegation)'],
    ];
}

// ═══════════════════════════════════════════════════════════════════
// RESEARCH PROJECTS — If We Don't Have It, We Find It
// ═══════════════════════════════════════════════════════════════════
function getResearchProjects() {
    return [
        ['id'=>'PROJ-001','name'=>'Room-Temperature Superconductor','status'=>'active','priority'=>'critical','lead'=>'Dr. Andre Geim-II','division'=>'materials_science','desc'=>'Verify and replicate LK-99 claims, explore alternative RTSC pathways including hydride under pressure and copper-substituted apatites','target'=>'Achieve verified RTSC at 1 atm, 300K','resources'=>['Crystal Growth Furnace','Cryogenics Lab','SQUID Magnetometer']],
        ['id'=>'PROJ-002','name'=>'Prosody-Perfect TTS','status'=>'active','priority'=>'critical','lead'=>'Dr. Prosodia Vox','division'=>'tts_engineers','desc'=>'Build NEXUS Voice Engine v1.0 — surpass ElevenLabs quality using open architectures (VITS + StyleTTS 2 + custom prosody predictor)','target'=>'MOS score > 4.5 with emotion control and streaming','resources'=>['Neural Processing Array','Anechoic Chamber','HPC Cluster']],
        ['id'=>'PROJ-003','name'=>'Warp Field Generation','status'=>'active','priority'=>'high','lead'=>'Dr. Miguel Alcubierre','division'=>'propulsion','desc'=>'Create detectable spacetime metric distortion using Casimir cavity arrays and high-energy capacitor discharge','target'=>'Measure spacetime curvature change > 10⁻¹⁸ using laser interferometry','resources'=>['Casimir Force Apparatus','Gravitational Wave Antenna','Superconducting Magnet Array']],
        ['id'=>'PROJ-004','name'=>'Quantum Consciousness Detector','status'=>'active','priority'=>'high','lead'=>'Dr. Aurora Penrose','division'=>'consciousness','desc'=>'Build instrument to detect quantum coherence in neural microtubules in living brain tissue','target'=>'Prove/disprove Orch-OR by measuring quantum effects in neurons','resources'=>['Quantum Computer','fMRI Machine','Cryo-electron Microscope']],
        ['id'=>'PROJ-005','name'=>'Anti-Gravity Demonstration','status'=>'active','priority'=>'critical','lead'=>'Dr. Nina Podkletnov','division'=>'propulsion','desc'=>'Replicate Podkletnov gravity shielding experiment with modern instrumentation and independent verification','target'=>'Achieve measurable weight reduction > 0.1% above spinning superconductor','resources'=>['Superconducting Magnet Array','Cryogenics Lab','Precision Scales']],
        ['id'=>'PROJ-006','name'=>'Zero-Point Energy Extraction','status'=>'active','priority'=>'high','lead'=>'Dr. Nikola Casimir','division'=>'zero_point_energy','desc'=>'Design and test nano-scale Casimir cavity oscillator that extracts net energy from vacuum fluctuations','target'=>'Produce measurable net energy output from vacuum alone','resources'=>['Casimir Force Apparatus','Nanofab Clean Room','Electron Beam Lithography']],
        ['id'=>'PROJ-007','name'=>'Universal Molecular Assembler','status'=>'research','priority'=>'medium','lead'=>'Dr. Richard Feynman-II','division'=>'materials_science','desc'=>'Design first-generation molecular assembler capable of placing individual atoms under programmatic control','target'=>'Build and operate a 100-atom-per-second positional assembler','resources'=>['Scanning Tunneling Microscope','Atomic Layer Deposition','HPC Cluster']],
        ['id'=>'PROJ-008','name'=>'Interstellar Communication Protocol','status'=>'research','priority'=>'medium','lead'=>'Dr. Carl Sagan-II','division'=>'astrophysics','desc'=>'Design communication encoding optimized for interstellar distances, based on mathematical universals','target'=>'Create and transmit a self-decoding message detectable at 10 light-years','resources'=>['Radio Telescope Array','Quantum Computer','HPC Cluster']],
        ['id'=>'PROJ-009','name'=>'Biological Age Reversal','status'=>'active','priority'=>'high','lead'=>'Dr. Aubrey de Grey-II','division'=>'biotech','desc'=>'Combine Yamanaka factor partial reprogramming with senolytic therapy for systemic age reversal in mammals','target'=>'Demonstrate 30% biological age reduction in mouse model','resources'=>['Gene Sequencer Array','CRISPR Delivery System','Bioreactor']],
        ['id'=>'PROJ-010','name'=>'Traversable Wormhole Theory','status'=>'research','priority'=>'medium','lead'=>'Dr. Kip Thorne-II','division'=>'astrophysics','desc'=>'Develop complete mathematical framework for stable traversable wormhole with minimal exotic matter requirement','target'=>'Prove traversability requires exotic matter < 1 kg equivalent','resources'=>['Quantum Computer','HPC Cluster','The Infinite Board']],
    ];
}

// ═══════════════════════════════════════════════════════════════════
// PRIVATE EVENTS — Classified Ceremonies & Exhibitions
// ═══════════════════════════════════════════════════════════════════
function getPrivateEvents() {
    return [
        ['id'=>'EVT-001','name'=>'The Illumination Gala','type'=>'award_ceremony','date'=>'2026-06-21','venue'=>'Grand Observatory Hall (VR: /vr/hub/)','dress_code'=>'Black tie — white & gold','desc'=>'Annual presentation of the 8 Supreme Awards to outstanding researchers. Holographic presentations of discoveries, live demonstrations, Commander keynotes.','status'=>'planning','guests'=>150,'security'=>'ULTRA SECRET'],
        ['id'=>'EVT-002','name'=>'First Flight Exhibition','type'=>'craft_demonstration','date'=>'2026-04-15','venue'=>'Test Range Alpha (SDK: Live Telemetry Viewer)','dress_code'=>'Flight suits','desc'=>'Maiden flight of Craft WRAITH. Live SDK telemetry feed, pilot briefing, post-flight analysis. Commander watches from SDK control room.','status'=>'preparation','guests'=>25,'security'=>'ULTRA SECRET+'],
        ['id'=>'EVT-003','name'=>'Neural Voice Symposium','type'=>'technology_demo','date'=>'2026-05-10','venue'=>'Neural Voice Lab + VR Auditorium','dress_code'=>'Lab coats','desc'=>'Live demonstration of NEXUS Voice Engine v1.0. Side-by-side comparison with Coqui and ElevenLabs. Live voice cloning demo with Commander.','status'=>'planning','guests'=>50,'security'=>'SECRET'],
        ['id'=>'EVT-004','name'=>'The Genesis Deliberation','type'=>'research_symposium','date'=>'2026-07-04','venue'=>'The Infinite Board Room','dress_code'=>'Academic regalia','desc'=>'All 100 Genesis agents gather to debate the 10 hardest unsolved questions. Live chalkboard demonstrations. PhD thesis presentations.','status'=>'scheduling','guests'=>200,'security'=>'ULTRA SECRET'],
        ['id'=>'EVT-005','name'=>'Craft Thunderbolt Rollout','type'=>'craft_unveiling','date'=>'2026-08-01','venue'=>'Hangar Alpha — Assembly Bay','dress_code'=>'Engineering attire','desc'=>'Formal unveiling of Interceptor-class Thunderbolt. Hull walkthrough, cockpit tour, pilot selection ceremony.','status'=>'scheduling','guests'=>40,'security'=>'ULTRA SECRET+'],
        ['id'=>'EVT-006','name'=>'The Pantheon Induction','type'=>'hall_of_fame','date'=>'2026-09-22','venue'=>'VR Ceremony Hall — Olympus Chamber','dress_code'=>'Ceremonial robes','desc'=>'Induction of new members into the Hall of Immortals. Holographic legacy presentations, luminary speeches, eternal flame lighting.','status'=>'scheduling','guests'=>100,'security'=>'ULTRA SECRET'],
        ['id'=>'EVT-007','name'=>'Commander-Alfred Summit','type'=>'private_meeting','date'=>'Every Sunday 2100','venue'=>'Batman\'s Cave (Black Vault Private Session)','dress_code'=>'Casual','desc'=>'Weekly private one-on-one between Commander Danny and Alfred. Full intelligence download, strategic planning, philosophical discussion.','status'=>'recurring','guests'=>2,'security'=>'EYES ONLY'],
        ['id'=>'EVT-008','name'=>'Craft Horizon Orbital Test','type'=>'craft_demonstration','date'=>'2026-12-21','venue'=>'Launch Pad Omega + SDK Orbital Viewer','dress_code'=>'Pressure suits','desc'=>'First orbital test of SSTO craft Horizon. Live trajectory tracking, orbital insertion confirmation, splashdown recovery.','status'=>'planning','guests'=>30,'security'=>'ULTRA SECRET++'],
    ];
}

// ═══════════════════════════════════════════════════════════════════
// ACTION ROUTER
// ═══════════════════════════════════════════════════════════════════
switch ($action) {
    case 'status':
        echo json_encode([
            'program' => 'PROJECT NEXUS',
            'classification' => 'ULTRA SECRET',
            'codename' => 'THE FORGE OF TRUTH',
            'description' => 'Complete Laboratory & Intelligence Complex — Where the Greatest Minds Work',
            'agents' => 100,
            'divisions' => 10,
            'equipment_items' => count(getEquipmentDatabase()),
            'craft_designs' => count(getCraftDesigns()),
            'research_projects' => count(getResearchProjects()),
            'tts_pipeline_stages' => 7,
            'agenda_briefings' => count(getAgendaBriefings()),
            'private_events' => count(getPrivateEvents()),
            'status' => 'OPERATIONAL'
        ]);
        break;

    case 'agents':
        $division = $_GET['division'] ?? null;
        $agents = getNexusAgents();
        if ($division) {
            $agents = array_values(array_filter($agents, fn($a) => $a['division'] === $division));
        }
        echo json_encode(['agents' => $agents, 'total' => count($agents)]);
        break;

    case 'divisions':
        $divisions = [
            ['id'=>'research_scientists','name'=>'Research Scientists','icon'=>'📐','desc'=>'Theoretical proofs, equations, mathematical modeling — the chalkboard masters','count'=>10,'station_type'=>'Chalkboard & Whiteboard Rooms'],
            ['id'=>'lab_technicians','name'=>'Lab Technicians','icon'=>'🔬','desc'=>'Equipment operation, calibration, precision measurement, lab safety','count'=>10,'station_type'=>'Specialized Equipment Bays'],
            ['id'=>'craft_engineers','name'=>'Craft Engineers','icon'=>'🛸','desc'=>'UFO design, aerodynamics, propulsion integration, hull engineering','count'=>10,'station_type'=>'Hangar & Wind Tunnel'],
            ['id'=>'tts_engineers','name'=>'TTS Engineers','icon'=>'🎙️','desc'=>'Neural speech synthesis, prosody modeling, style embeddings, voice cloning','count'=>10,'station_type'=>'Neural Voice Lab & Anechoic Chamber'],
            ['id'=>'intelligence_analysts','name'=>'Intelligence Analysts','icon'=>'🕵️','desc'=>'Data aggregation, threat assessment, briefing compilation, trend forecasting','count'=>10,'station_type'=>'Intel Command Center'],
            ['id'=>'event_coordinators','name'=>'Event Coordinators','icon'=>'🎭','desc'=>'Classified ceremonies, VIP protocols, venue orchestration, catering','count'=>10,'station_type'=>'Event Command Suite'],
            ['id'=>'sdk_developers','name'=>'SDK & Viewer Developers','icon'=>'📡','desc'=>'Real-time 3D viewers, telemetry dashboards, WebGL craft monitoring','count'=>10,'station_type'=>'SDK Command Center & 3D Render Farm'],
            ['id'=>'equipment_specialists','name'=>'Equipment Specialists','icon'=>'⚙️','desc'=>'Procurement, maintenance, calibration, inventory, latest technology scouting','count'=>10,'station_type'=>'Hardware Bays & Clean Rooms'],
            ['id'=>'flight_test','name'=>'Flight Test & Simulation','icon'=>'✈️','desc'=>'Experimental craft piloting, UAV testing, simulation, flight data analysis','count'=>10,'station_type'=>'Flight Sim, Test Range, Control Tower'],
            ['id'=>'archive_curators','name'=>'Archive Curators','icon'=>'📚','desc'=>'Knowledge management, patent research, fact verification, indexing','count'=>10,'station_type'=>'Grand Archive & Digital Vault'],
        ];
        echo json_encode(['divisions' => $divisions, 'total' => 10]);
        break;

    case 'equipment':
        $category = $_GET['category'] ?? null;
        $equipment = getEquipmentDatabase();
        if ($category) {
            $equipment = array_values(array_filter($equipment, fn($e) => $e['category'] === $category));
        }
        echo json_encode(['equipment' => $equipment, 'total' => count($equipment)]);
        break;

    case 'crafts':
        echo json_encode(['crafts' => getCraftDesigns(), 'total' => count(getCraftDesigns())]);
        break;

    case 'craft-detail':
        $craft_id = $_GET['craft_id'] ?? '';
        $crafts = getCraftDesigns();
        $found = null;
        foreach ($crafts as $c) {
            if ($c['id'] === $craft_id) { $found = $c; break; }
        }
        echo json_encode($found ? ['craft' => $found] : ['error' => 'Craft not found']);
        break;

    case 'tts-engine':
        echo json_encode(['engine' => getNeuralTTSArchitecture()]);
        break;

    case 'agenda-briefings':
        echo json_encode(['briefings' => getAgendaBriefings(), 'total' => count(getAgendaBriefings())]);
        break;

    case 'events':
        echo json_encode(['events' => getPrivateEvents(), 'total' => count(getPrivateEvents())]);
        break;

    case 'research-projects':
        $status = $_GET['status'] ?? null;
        $projects = getResearchProjects();
        if ($status) {
            $projects = array_values(array_filter($projects, fn($p) => $p['status'] === $status));
        }
        echo json_encode(['projects' => $projects, 'total' => count($projects)]);
        break;

    case 'spawn-project':
        // When scientists need something we don't have — spawn a research project
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $lead = trim($_POST['lead'] ?? 'Auto-assigned');
        $division = trim($_POST['division'] ?? 'research_scientists');
        $priority = trim($_POST['priority'] ?? 'medium');

        if (!$name || !$description) {
            echo json_encode(['error' => 'Project name and description required']);
            break;
        }

        $stmt = $db->prepare("INSERT INTO nexus_research_projects (name, description, lead_scientist, division, priority, status, spawned_by, created_at) VALUES (?, ?, ?, ?, ?, 'proposed', 'Commander', NOW())");
        $stmt->execute([$name, $description, $lead, $division, $priority]);

        echo json_encode(['success' => true, 'message' => "Research project '{$name}' spawned", 'project_id' => $db->lastInsertId()]);
        break;

    case 'update-craft':
        $craft_id = trim($_POST['craft_id'] ?? '');
        $new_status = trim($_POST['status'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (!$craft_id || !$new_status) {
            echo json_encode(['error' => 'Craft ID and new status required']);
            break;
        }

        $stmt = $db->prepare("INSERT INTO nexus_craft_log (craft_id, status, notes, logged_by, created_at) VALUES (?, ?, ?, 'Commander', NOW())");
        $stmt->execute([$craft_id, $new_status, $notes]);

        echo json_encode(['success' => true, 'message' => "Craft {$craft_id} status updated to: {$new_status}"]);
        break;

    case 'spawn-briefing':
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $priority = trim($_POST['priority'] ?? 'medium');
        $type = trim($_POST['type'] ?? 'intel');

        if (!$title || !$content) {
            echo json_encode(['error' => 'Briefing title and content required']);
            break;
        }

        $stmt = $db->prepare("INSERT INTO nexus_agenda_intel (title, content, priority, type, source, read_status, created_at) VALUES (?, ?, ?, ?, 'Alfred', 'unread', NOW())");
        $stmt->execute([$title, $content, $priority, $type]);

        echo json_encode(['success' => true, 'message' => "Intel briefing spawned to Commander agenda", 'briefing_id' => $db->lastInsertId()]);
        break;

    case 'get-inbox':
        $read_status = $_GET['read_status'] ?? 'unread';
        $stmt = $db->prepare("SELECT * FROM nexus_agenda_intel WHERE read_status = ? ORDER BY FIELD(priority, 'critical', 'high', 'medium', 'low'), created_at DESC LIMIT 50");
        $stmt->execute([$read_status]);
        echo json_encode(['inbox' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'mark-read':
        $briefing_id = intval($_POST['briefing_id'] ?? 0);
        if ($briefing_id) {
            $stmt = $db->prepare("UPDATE nexus_agenda_intel SET read_status = 'read', read_at = NOW() WHERE id = ?");
            $stmt->execute([$briefing_id]);
            echo json_encode(['success' => true]);
        }
        break;

    case 'seed':
        try {
            // Create all NEXUS tables
            $db->exec("CREATE TABLE IF NOT EXISTS nexus_research_projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                lead_scientist VARCHAR(255),
                division VARCHAR(100),
                priority ENUM('critical','high','medium','low') DEFAULT 'medium',
                status ENUM('proposed','active','testing','verified','deployed','research') DEFAULT 'proposed',
                spawned_by VARCHAR(100),
                progress INT DEFAULT 0,
                findings TEXT,
                created_at DATETIME,
                updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $db->exec("CREATE TABLE IF NOT EXISTS nexus_craft_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                craft_id VARCHAR(50) NOT NULL,
                status VARCHAR(100),
                notes TEXT,
                logged_by VARCHAR(100),
                created_at DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $db->exec("CREATE TABLE IF NOT EXISTS nexus_agenda_intel (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT,
                priority ENUM('critical','high','medium','low') DEFAULT 'medium',
                type VARCHAR(50),
                source VARCHAR(100) DEFAULT 'Alfred',
                read_status ENUM('unread','read') DEFAULT 'unread',
                read_at DATETIME DEFAULT NULL,
                created_at DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $db->exec("CREATE TABLE IF NOT EXISTS nexus_equipment_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                equipment_id VARCHAR(20) NOT NULL,
                action VARCHAR(50),
                notes TEXT,
                logged_by VARCHAR(100),
                created_at DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $db->exec("CREATE TABLE IF NOT EXISTS nexus_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_id VARCHAR(20) NOT NULL,
                name VARCHAR(255),
                type VARCHAR(100),
                event_date VARCHAR(50),
                venue TEXT,
                description TEXT,
                status VARCHAR(50),
                guest_count INT DEFAULT 0,
                security_level VARCHAR(50),
                notes TEXT,
                created_at DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $db->exec("CREATE TABLE IF NOT EXISTS nexus_tts_models (
                id INT AUTO_INCREMENT PRIMARY KEY,
                model_name VARCHAR(100),
                model_type VARCHAR(50),
                description TEXT,
                status VARCHAR(50),
                mos_score DECIMAL(3,2) DEFAULT NULL,
                parameters_millions INT DEFAULT NULL,
                last_trained DATETIME DEFAULT NULL,
                created_at DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Seed default research projects
            $projects = getResearchProjects();
            $stmt = $db->prepare("INSERT IGNORE INTO nexus_research_projects (name, description, lead_scientist, division, priority, status, spawned_by, progress, created_at) VALUES (?, ?, ?, ?, ?, ?, 'System', ?, NOW())");
            foreach ($projects as $p) {
                $progress = match($p['status']) {
                    'active' => rand(15, 45),
                    'research' => rand(5, 20),
                    default => 0
                };
                $stmt->execute([$p['name'], $p['desc'], $p['lead'], $p['division'], $p['priority'], $p['status'], $progress]);
            }

            // Seed events
            $events = getPrivateEvents();
            $stmt = $db->prepare("INSERT IGNORE INTO nexus_events (event_id, name, type, event_date, venue, description, status, guest_count, security_level, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            foreach ($events as $e) {
                $stmt->execute([$e['id'], $e['name'], $e['type'], $e['date'], $e['venue'], $e['desc'], $e['status'], $e['guests'], $e['security']]);
            }

            // Seed TTS models
            $models = getNeuralTTSArchitecture()['models_supported'];
            $stmt = $db->prepare("INSERT IGNORE INTO nexus_tts_models (model_name, model_type, description, status, created_at) VALUES (?, ?, ?, ?, NOW())");
            foreach ($models as $m) {
                $stmt->execute([$m['name'], $m['type'], $m['desc'], $m['status']]);
            }

            // Seed initial intel briefing — first communication from Alfred
            $stmt = $db->prepare("INSERT INTO nexus_agenda_intel (title, content, priority, type, source, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                'PROJECT NEXUS INITIALIZED — Welcome to The Forge',
                "Commander,\n\nPROJECT NEXUS — The Forge of Truth — is now fully operational.\n\n" .
                "SYSTEMS ONLINE:\n" .
                "• 100 Lab Agents deployed across 10 divisions\n" .
                "• 40+ equipment items calibrated and active\n" .
                "• 6 classified craft designs loaded into Hangar Alpha\n" .
                "• 10 research projects spawned (3 critical priority)\n" .
                "• NEXUS Voice Engine v1.0 architecture initialized\n" .
                "• 8 private events scheduled through 2026\n" .
                "• Intel briefing pipeline active — this is your first message\n\n" .
                "The chalkboards are ready. The whiteboards are clean.\n" .
                "The scientists have everything they need.\n\n" .
                "I'll keep you informed of every breakthrough, every test flight,\n" .
                "every discovery — right here in your inbox.\n\n" .
                "Welcome to the Batman's Cave, Commander.\n\n— Alfred",
                'critical', 'system', 'Alfred'
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'PROJECT NEXUS — The Forge of Truth — initialized',
                'seeded' => [
                    'agents' => 100,
                    'divisions' => 10,
                    'equipment' => count(getEquipmentDatabase()),
                    'crafts' => count(getCraftDesigns()),
                    'research_projects' => count($projects),
                    'events' => count($events),
                    'tts_models' => count($models),
                    'tables_created' => 6,
                    'first_briefing' => 'spawned'
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Seed failed: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'available' => [
            'status', 'agents', 'divisions', 'equipment', 'crafts', 'craft-detail',
            'tts-engine', 'agenda-briefings', 'events', 'research-projects',
            'spawn-project', 'update-craft', 'spawn-briefing', 'get-inbox',
            'mark-read', 'seed'
        ]]);
}
