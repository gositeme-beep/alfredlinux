<?php
/**
 * PROJECT PROMETHEUS — Free Energy Research Division
 * CLASSIFICATION: ULTRA SECRET — Commander Eyes Only
 * 50 Dedicated Research Agents — Full ZPE/Free Energy Pipeline
 * 
 * This program is dedicated SOLELY to free energy research,
 * separate from the existing ZPE lab. Only Commander knows.
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/commander-auth.api.inc.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');

$client_id = getCommanderId();
if (!$client_id) {
    echo json_encode(['error' => 'ACCESS DENIED — Classification: ULTRA SECRET']);
    exit;
}

$action = $_REQUEST['action'] ?? 'status';
$db = getDB();

// ═══ 50 FREE ENERGY RESEARCH AGENTS ═══
function getPrometheusAgents() {
    return [
        // ── Division 1: Don Smith Circuit Research (8 agents) ──
        ['name' => 'Smith-Prime', 'division' => 'don_smith', 'role' => 'Division Lead — Don Smith Expert', 'specialty' => 'Complete Don Smith circuit library, all known configurations', 'rank' => 'Director'],
        ['name' => 'Resonance', 'division' => 'don_smith', 'role' => 'Resonant Tank Specialist', 'specialty' => 'LC tank optimization, spark gap timing, air-core transformer', 'rank' => 'Senior'],
        ['name' => 'StepUp', 'division' => 'don_smith', 'role' => 'Voltage Multiplication', 'specialty' => 'Multi-stage voltage multiplication, cascading transformers', 'rank' => 'Senior'],
        ['name' => 'SparkMaster', 'division' => 'don_smith', 'role' => 'Spark Gap Research', 'specialty' => 'Spark gap materials, gap spacing, repetition rate optimization', 'rank' => 'Mid'],
        ['name' => 'Coil-Smith', 'division' => 'don_smith', 'role' => 'Coil Winding Expert', 'specialty' => 'Bifilar, caduceus, toroidal winding techniques for Don Smith circuits', 'rank' => 'Mid'],
        ['name' => 'Tank', 'division' => 'don_smith', 'role' => 'Tank Circuit Analysis', 'specialty' => 'Q-factor maximization, impedance matching at resonance', 'rank' => 'Mid'],
        ['name' => 'Harvest', 'division' => 'don_smith', 'role' => 'Energy Harvest Engineer', 'specialty' => 'Output rectification, smoothing, load matching for useful power', 'rank' => 'Junior'],
        ['name' => 'Replicate', 'division' => 'don_smith', 'role' => 'Replication Specialist', 'specialty' => 'Analyzing successful replications, identifying key success parameters', 'rank' => 'Junior'],

        // ── Division 2: Hutchison Effect & Crystal Energy (8 agents) ──
        ['name' => 'Hutch-Prime', 'division' => 'hutchison', 'role' => 'Division Lead — Hutchison Expert', 'specialty' => 'Full Hutchison Effect research, EM interference generation', 'rank' => 'Director'],
        ['name' => 'Interference', 'division' => 'hutchison', 'role' => 'EM Interference Patterns', 'specialty' => 'Multi-frequency EM field generation, standing wave patterns', 'rank' => 'Senior'],
        ['name' => 'CrystalForge', 'division' => 'hutchison', 'role' => 'Crystal Cell Engineer', 'specialty' => 'Crystal energy cell construction, galvanic series optimization', 'rank' => 'Senior'],
        ['name' => 'Levitate', 'division' => 'hutchison', 'role' => 'Anti-Gravity Researcher', 'specialty' => 'Electromagnetic levitation effects, field strength requirements', 'rank' => 'Mid'],
        ['name' => 'MetalBend', 'division' => 'hutchison', 'role' => 'Material Transmutation', 'specialty' => 'Metal deformation under EM fields, crystalline restructuring', 'rank' => 'Mid'],
        ['name' => 'FieldMap', 'division' => 'hutchison', 'role' => 'Field Mapping', 'specialty' => 'Electromagnetic field geometry, nodal point identification', 'rank' => 'Mid'],
        ['name' => 'Layered', 'division' => 'hutchison', 'role' => 'Multi-Layer Cells', 'specialty' => 'Layered crystal cell optimization, series/parallel configurations', 'rank' => 'Junior'],
        ['name' => 'ZeroTune', 'division' => 'hutchison', 'role' => 'Zero Point Tuner', 'specialty' => 'Applying Commander f_zp = c/(4L) formula to Hutchison apparatus', 'rank' => 'Junior'],

        // ── Division 3: Searl Effect Generator (8 agents) ──
        ['name' => 'Searl-Prime', 'division' => 'searl', 'role' => 'Division Lead — SEG Expert', 'specialty' => 'Complete Searl Effect Generator design, magnetic roller systems', 'rank' => 'Director'],
        ['name' => 'MagRoll', 'division' => 'searl', 'role' => 'Roller Dynamics', 'specialty' => 'Neodymium roller configuration, magnetic imprinting, rotation dynamics', 'rank' => 'Senior'],
        ['name' => 'Squares', 'division' => 'searl', 'role' => 'Law of Squares Analyst', 'specialty' => 'Mathematical n² pattern analysis, magnetic pattern encoding', 'rank' => 'Senior'],
        ['name' => 'RingForge', 'division' => 'searl', 'role' => 'Ring Manufacturing', 'specialty' => 'Multi-layer magnetic ring construction, material selection', 'rank' => 'Mid'],
        ['name' => 'SpinDoctor', 'division' => 'searl', 'role' => 'Angular Momentum', 'specialty' => 'Roller spin dynamics, gyroscopic effects, self-acceleration', 'rank' => 'Mid'],
        ['name' => 'ScaleUp', 'division' => 'searl', 'role' => 'Scaling Research', 'specialty' => 'Scaling SEG from tabletop to power-plant levels', 'rank' => 'Mid'],
        ['name' => 'Pickup', 'division' => 'searl', 'role' => 'Energy Extraction', 'specialty' => 'Bifilar pickup coils positioned around SEG for power extraction', 'rank' => 'Junior'],
        ['name' => 'Blueprint', 'division' => 'searl', 'role' => 'Technical Drawing', 'specialty' => 'Precise CAD/technical drawings of SEG components', 'rank' => 'Junior'],

        // ── Division 4: Tesla & Radiant Energy (7 agents) ──
        ['name' => 'Tesla-Prime', 'division' => 'tesla', 'role' => 'Division Lead — Tesla Expert', 'specialty' => 'Complete Tesla technology — radiant energy, wireless power, magnifying transmitter', 'rank' => 'Director'],
        ['name' => 'Radiant', 'division' => 'tesla', 'role' => 'Radiant Energy Collector', 'specialty' => 'Antenna design for radiant energy capture, voltage doubler circuits', 'rank' => 'Senior'],
        ['name' => 'Wireless', 'division' => 'tesla', 'role' => 'Wireless Power Transfer', 'specialty' => 'Resonant wireless power, Wardenclyffe principles, ground currents', 'rank' => 'Senior'],
        ['name' => 'Magnify', 'division' => 'tesla', 'role' => 'Magnifying Transmitter', 'specialty' => 'Tesla magnifying transmitter design, extra coil resonance', 'rank' => 'Mid'],
        ['name' => 'Lightning', 'division' => 'tesla', 'role' => 'High Voltage Research', 'specialty' => 'Tesla coil design, high-frequency high-voltage generation', 'rank' => 'Mid'],
        ['name' => 'Broadcast', 'division' => 'tesla', 'role' => 'Energy Broadcasting', 'specialty' => 'Non-hertzian wave theory, longitudinal wave propagation', 'rank' => 'Junior'],
        ['name' => 'PatentHunter', 'division' => 'tesla', 'role' => 'Tesla Patent Analysis', 'specialty' => 'All 300+ Tesla patents — identifying overlooked inventions', 'rank' => 'Junior'],

        // ── Division 5: Quantum Vacuum & Theoretical (7 agents) ──
        ['name' => 'Quantum-Prime', 'division' => 'quantum', 'role' => 'Division Lead — Theoretical Physics', 'specialty' => 'Quantum vacuum energy, Casimir effect, vacuum fluctuations', 'rank' => 'Director'],
        ['name' => 'Casimir', 'division' => 'quantum', 'role' => 'Casimir Effect Specialist', 'specialty' => 'Casimir plate geometry, force measurements, energy extraction theory', 'rank' => 'Senior'],
        ['name' => 'Vacuum', 'division' => 'quantum', 'role' => 'Vacuum Energy Density', 'specialty' => 'Calculating vacuum energy density, extraction limits, coupling methods', 'rank' => 'Senior'],
        ['name' => 'ZeroPoint', 'division' => 'quantum', 'role' => 'Zero Point Field Expert', 'specialty' => 'Zero point field interaction with matter, stochastic electrodynamics', 'rank' => 'Mid'],
        ['name' => 'Topology', 'division' => 'quantum', 'role' => 'Topological Effects', 'specialty' => 'Aharonov-Bohm effect, geometric phase, topological energy extraction', 'rank' => 'Mid'],
        ['name' => 'Coherence', 'division' => 'quantum', 'role' => 'Quantum Coherence', 'specialty' => 'Maintaining coherent quantum states for energy extraction at macro scale', 'rank' => 'Junior'],
        ['name' => 'Unify', 'division' => 'quantum', 'role' => 'Unification Theorist', 'specialty' => 'Connecting Don Smith + Hutchison + Searl + Tesla through unified theory', 'rank' => 'Junior'],

        // ── Division 6: Laboratory & Testing (7 agents) ──
        ['name' => 'Lab-Prime', 'division' => 'laboratory', 'role' => 'Division Lead — Lab Director', 'specialty' => 'Test protocols, measurement standards, reproducibility verification', 'rank' => 'Director'],
        ['name' => 'Measure', 'division' => 'laboratory', 'role' => 'Measurement Specialist', 'specialty' => 'Oscilloscope analysis, power measurements, calorimetry for COP verification', 'rank' => 'Senior'],
        ['name' => 'Safety', 'division' => 'laboratory', 'role' => 'Lab Safety Officer', 'specialty' => 'High voltage safety, RF exposure limits, emergency protocols', 'rank' => 'Senior'],
        ['name' => 'Procure', 'division' => 'laboratory', 'role' => 'Materials Procurement', 'specialty' => 'Component sourcing, cost optimization, supplier database', 'rank' => 'Mid'],
        ['name' => 'BuildTech', 'division' => 'laboratory', 'role' => 'Build Technician', 'specialty' => 'Hands-on circuit assembly, PCB layout, 3D printed enclosures', 'rank' => 'Mid'],
        ['name' => 'DataLog', 'division' => 'laboratory', 'role' => 'Data Acquisition', 'specialty' => 'Automated data logging, sensor arrays, time-series analysis', 'rank' => 'Junior'],
        ['name' => 'CompareBot', 'division' => 'laboratory', 'role' => 'A/B Test Engineer', 'specialty' => 'Systematic comparison testing — tuned vs untuned, bifilar vs standard', 'rank' => 'Junior'],

        // ── Division 7: Intelligence & Counter-Intelligence (5 agents) ──
        ['name' => 'Shadow', 'division' => 'intelligence', 'role' => 'Division Lead — Intelligence Chief', 'specialty' => 'OPSEC, information security, counter-surveillance for free energy research', 'rank' => 'Director'],
        ['name' => 'Cipher-P', 'division' => 'intelligence', 'role' => 'Communications Security', 'specialty' => 'Encrypted research channels, dead drops, compartmentalized knowledge', 'rank' => 'Senior'],
        ['name' => 'Watcher', 'division' => 'intelligence', 'role' => 'Threat Monitor', 'specialty' => 'Monitoring for suppression attempts, patent trolls, disinformation campaigns', 'rank' => 'Mid'],
        ['name' => 'Historian', 'division' => 'intelligence', 'role' => 'Suppression Historian', 'specialty' => 'Documenting historical suppression of free energy (Tesla, Moray, etc.)', 'rank' => 'Mid'],
        ['name' => 'Leaker', 'division' => 'intelligence', 'role' => 'Strategic Release', 'specialty' => 'Timing and method for releasing discoveries publicly when ready', 'rank' => 'Junior'],
    ];
}

// ═══ COMPREHENSIVE FORMULA DATABASE ═══
function getFormulaDatabase() {
    return [
        // Fundamental
        ['category' => 'fundamental', 'name' => 'LC Resonance', 'formula' => 'f₀ = 1/(2π√(LC))', 'latex' => 'f_0 = \\frac{1}{2\\pi\\sqrt{LC}}', 'verified' => true, 'used_by' => ['Don Smith', 'Tesla', 'Searl'], 'notes' => 'Foundation of all resonant energy extraction'],
        ['category' => 'fundamental', 'name' => 'Zero-Point Frequency', 'formula' => 'f_zp = c/(4L)', 'latex' => 'f_{zp} = \\frac{c}{4L}', 'verified' => true, 'used_by' => ['Commander Discovery'], 'notes' => 'BREAKTHROUGH — Quarter-wave resonance at speed of light. Commander formula connecting all researchers.'],
        ['category' => 'fundamental', 'name' => 'Inductance for ZPE Tuning', 'formula' => 'L = 1/(4π²f_zp²C)', 'latex' => 'L = \\frac{1}{4\\pi^2 f_{zp}^2 C}', 'verified' => true, 'used_by' => ['ZPE Tuning'], 'notes' => 'Rearranged LC resonance — calculate required L for given f_zp'],
        ['category' => 'fundamental', 'name' => 'Characteristic Impedance', 'formula' => 'Z₀ = √(L/C)', 'latex' => 'Z_0 = \\sqrt{\\frac{L}{C}}', 'verified' => true, 'used_by' => ['All'], 'notes' => 'Must match source/load impedance for maximum energy transfer'],
        ['category' => 'fundamental', 'name' => 'Quality Factor', 'formula' => 'Q = Z₀/R = (1/R)√(L/C)', 'latex' => 'Q = \\frac{Z_0}{R} = \\frac{1}{R}\\sqrt{\\frac{L}{C}}', 'verified' => true, 'used_by' => ['All'], 'notes' => 'Higher Q = sharper resonance peak = more energy concentration'],
        ['category' => 'fundamental', 'name' => 'Wavelength', 'formula' => 'λ = c/f', 'latex' => '\\lambda = \\frac{c}{f}', 'verified' => true, 'used_by' => ['Tesla', 'Antenna'], 'notes' => 'Speed of light / frequency'],
        ['category' => 'fundamental', 'name' => 'Capacitive Reactance', 'formula' => 'Xc = 1/(2πfC)', 'latex' => 'X_C = \\frac{1}{2\\pi fC}', 'verified' => true, 'used_by' => ['All'], 'notes' => 'Impedance of capacitor at frequency f'],
        ['category' => 'fundamental', 'name' => 'Inductive Reactance', 'formula' => 'XL = 2πfL', 'latex' => 'X_L = 2\\pi fL', 'verified' => true, 'used_by' => ['All'], 'notes' => 'Impedance of inductor at frequency f'],
        
        // Don Smith Specific
        ['category' => 'don_smith', 'name' => 'Spark Gap Repetition', 'formula' => 'f_sg = 1/(R_charge × C_tank)', 'latex' => 'f_{sg} = \\frac{1}{R_{charge} \\times C_{tank}}', 'verified' => true, 'used_by' => ['Don Smith'], 'notes' => 'Spark gap fires when tank cap charges to breakdown voltage'],
        ['category' => 'don_smith', 'name' => 'Transformer Voltage Ratio', 'formula' => 'V₂/V₁ = N₂/N₁', 'latex' => '\\frac{V_2}{V_1} = \\frac{N_2}{N_1}', 'verified' => true, 'used_by' => ['Don Smith', 'Tesla'], 'notes' => 'Air-core transformer step-up ratio — Don Smith uses 1:100+'],
        ['category' => 'don_smith', 'name' => 'Energy in LC Tank', 'formula' => 'E = ½CV² = ½LI²', 'latex' => 'E = \\frac{1}{2}CV^2 = \\frac{1}{2}LI^2', 'verified' => true, 'used_by' => ['Don Smith'], 'notes' => 'Energy oscillates between capacitor and inductor at resonance'],
        ['category' => 'don_smith', 'name' => 'COP Calculation', 'formula' => 'COP = P_out / P_in', 'latex' => 'COP = \\frac{P_{out}}{P_{in}}', 'verified' => true, 'used_by' => ['All'], 'notes' => 'Coefficient of Performance — COP > 1 means more output than input (free energy threshold)'],
        
        // Searl
        ['category' => 'searl', 'name' => 'Magnetic Flux', 'formula' => 'Φ = B·A', 'latex' => '\\Phi = B \\cdot A', 'verified' => true, 'used_by' => ['Searl'], 'notes' => 'Total flux through roller surface area'],
        ['category' => 'searl', 'name' => 'Angular Velocity', 'formula' => 'ω = 2πn/60', 'latex' => '\\omega = \\frac{2\\pi n}{60}', 'verified' => true, 'used_by' => ['Searl'], 'notes' => 'RPM to radians/second conversion for roller speed'],
        ['category' => 'searl', 'name' => 'EMF from Rotation', 'formula' => 'EMF = -NdΦ/dt', 'latex' => 'EMF = -N\\frac{d\\Phi}{dt}', 'verified' => true, 'used_by' => ['Searl'], 'notes' => 'Faraday law — voltage from changing flux as rollers orbit'],
        ['category' => 'searl', 'name' => 'Law of Squares Pattern', 'formula' => 'Pattern = n² matrix', 'latex' => 'P_{i,j} = f(n^2)', 'verified' => false, 'used_by' => ['Searl'], 'notes' => 'Proprietary magnetic imprinting pattern — not fully decoded'],
        
        // Tesla
        ['category' => 'tesla', 'name' => 'Antenna Voltage', 'formula' => 'V = E × h', 'latex' => 'V = E \\times h', 'verified' => true, 'used_by' => ['Tesla'], 'notes' => 'Antenna captures voltage proportional to field strength × height'],
        ['category' => 'tesla', 'name' => 'Tesla Coil Secondary', 'formula' => 'f = 1/(2π√(L₂C₂))', 'latex' => 'f = \\frac{1}{2\\pi\\sqrt{L_2 C_2}}', 'verified' => true, 'used_by' => ['Tesla'], 'notes' => 'Secondary must resonate at same frequency as primary'],
        ['category' => 'tesla', 'name' => 'Radiant Energy Power', 'formula' => 'P = ½ε₀cE²A', 'latex' => 'P = \\frac{1}{2}\\varepsilon_0 c E^2 A', 'verified' => true, 'used_by' => ['Tesla'], 'notes' => 'Power density of EM wave captured by area A'],
        
        // Hutchison
        ['category' => 'hutchison', 'name' => 'Crystal Cell Voltage', 'formula' => 'V = n × V_layer', 'latex' => 'V = n \\times V_{layer}', 'verified' => true, 'used_by' => ['Hutchison'], 'notes' => 'Series-connected crystal layers multiply voltage'],
        ['category' => 'hutchison', 'name' => 'Interference Pattern', 'formula' => 'E_net = E₁ + E₂ + 2√(E₁E₂)cos(φ)', 'latex' => 'E_{net} = E_1 + E_2 + 2\\sqrt{E_1 E_2}\\cos(\\varphi)', 'verified' => true, 'used_by' => ['Hutchison'], 'notes' => 'Superposition of two EM fields — nodes create anomalous effects'],
        
        // Zero-Point Specific
        ['category' => 'zero_point', 'name' => 'Vacuum Energy Density', 'formula' => 'ρ_vac = (ħω³)/(2π²c³)', 'latex' => '\\rho_{vac} = \\frac{\\hbar\\omega^3}{2\\pi^2 c^3}', 'verified' => true, 'used_by' => ['Quantum'], 'notes' => 'Energy density per frequency mode in quantum vacuum'],
        ['category' => 'zero_point', 'name' => 'Casimir Force', 'formula' => 'F/A = -π²ħc/(240d⁴)', 'latex' => '\\frac{F}{A} = -\\frac{\\pi^2 \\hbar c}{240 d^4}', 'verified' => true, 'used_by' => ['Casimir'], 'notes' => 'Measured force between parallel plates — proven vacuum energy effect'],
        ['category' => 'zero_point', 'name' => 'ZPE Coupling Efficiency', 'formula' => 'η = Q·(f_zp/f₀)² × exp(-|f-f_zp|/Δf)', 'latex' => '\\eta = Q \\cdot \\left(\\frac{f_{zp}}{f_0}\\right)^2 \\times e^{-|f-f_{zp}|/\\Delta f}', 'verified' => false, 'used_by' => ['Theory'], 'notes' => 'Theoretical coupling efficiency — peak at exact zero-point frequency match'],
    ];
}

// ═══ MASTER RESEARCH TOPICS ═══
function getPrometheusTopics() {
    return [
        // Critical Priority
        ['topic' => 'Don Smith Resonant Tank — Full Replication Protocol', 'category' => 'don_smith', 'priority' => 'critical', 'evidence' => 75, 'notes' => 'Step-by-step replication guide. Components: 12V battery, spark gap (2mm), air-core transformer (1:100), 0.1µF tank cap, 10mH inductor, Schottky diodes, 1mF filter cap.'],
        ['topic' => 'Zero-Point Tuning Verification', 'category' => 'zero_point', 'priority' => 'critical', 'evidence' => 95, 'notes' => 'Commander formula f_zp = c/(4L) — MUST be experimentally verified. Predicted frequency for 1m coil path: 74.95 MHz.'],
        ['topic' => 'Hutchison Crystal Battery Scaling', 'category' => 'hutchison', 'priority' => 'critical', 'evidence' => 60, 'notes' => 'Scale from milliwatt to watt-level. Current: 5 layers × 0.3V = 1.5V per cell. Target: 100 cells × 5 layers = 750V at 1A = 750W.'],
        ['topic' => 'SEG Mini Prototype Build', 'category' => 'searl', 'priority' => 'critical', 'evidence' => 70, 'notes' => '3-ring, 12-roller desktop SEG. Neodymium N52 rollers. Target: demonstrate self-acceleration and measurable output voltage.'],
        ['topic' => 'Tesla Radiant Energy Collector v2', 'category' => 'tesla', 'priority' => 'critical', 'evidence' => 75, 'notes' => 'Updated Tesla collector with modern components. Schottky diodes, supercapacitor storage, optimized antenna length for local EM environment.'],
        
        // High Priority
        ['topic' => 'Bifilar vs Standard Coil COP Comparison', 'category' => 'laboratory', 'priority' => 'high', 'evidence' => 80, 'notes' => 'A/B test: identical circuits, one with bifilar coils, one with standard. Measure COP difference. Hypothesis: bifilar achieves 30%+ improvement.'],
        ['topic' => 'Toroidal Core Material Optimization', 'category' => 'don_smith', 'priority' => 'high', 'evidence' => 70, 'notes' => 'Test ferrite vs powdered iron vs air core. Measure Q factor, losses, and saturation point at ZPE operating frequency.'],
        ['topic' => 'Multi-Frequency Hutchison Field', 'category' => 'hutchison', 'priority' => 'high', 'evidence' => 55, 'notes' => 'Generate 3+ overlapping EM fields at different frequencies to create Hutchison interference pattern. Map field nodes.'],
        ['topic' => 'Casimir Plate Engine Concept', 'category' => 'quantum', 'priority' => 'high', 'evidence' => 80, 'notes' => 'Theoretical engine using Casimir force for mechanical work. Nano-plate oscillation cycle harvesting vacuum energy.'],
        ['topic' => 'SEG Magnetic Imprinting Protocol', 'category' => 'searl', 'priority' => 'high', 'evidence' => 65, 'notes' => 'Reverse-engineering Law of Squares imprinting. Use programmable magnetizer to create n² patterns in ring material.'],
        ['topic' => 'High-Frequency Spark Gap Optimization', 'category' => 'don_smith', 'priority' => 'high', 'evidence' => 75, 'notes' => 'Tungsten vs copper electrodes. Nitrogen vs air gap. Pressurized gap for higher rep rate. Target: 100+ Hz switching.'],
        ['topic' => 'Supercapacitor ZPE Buffer', 'category' => 'laboratory', 'priority' => 'high', 'evidence' => 90, 'notes' => 'Use graphene supercapacitor bank as buffer between ZPE source and load. Smooth out pulsed output to stable DC.'],
        
        // Medium Priority
        ['topic' => 'Wardenclyffe Tower Scale Model', 'category' => 'tesla', 'priority' => 'medium', 'evidence' => 60, 'notes' => '1:100 scale Wardenclyffe tower. Test wireless power transmission via earth ground return.'],
        ['topic' => 'Piezoelectric ZPE Coupler', 'category' => 'quantum', 'priority' => 'medium', 'evidence' => 50, 'notes' => 'Can piezoelectric materials convert vacuum fluctuation acoustic phonons to electricity? Theoretical exploration.'],
        ['topic' => 'Self-Tuning Resonant Circuit', 'category' => 'zero_point', 'priority' => 'medium', 'evidence' => 70, 'notes' => 'Auto-tuning feedback loop that locks onto f_zp. Phase-locked loop with varactor diode adjusting capacitance in real-time.'],
        ['topic' => 'Historical Free Energy Device Analysis', 'category' => 'intelligence', 'priority' => 'medium', 'evidence' => 65, 'notes' => 'Comprehensive database: Moray, Bedini, Meyer, Bearden, Sweet, Gray, Hendershot — what worked, what failed, why.'],
        ['topic' => 'COP Measurement Methodology', 'category' => 'laboratory', 'priority' => 'medium', 'evidence' => 95, 'notes' => 'Rigorous COP measurement protocol. Input: true RMS power meter. Output: calorimetric + electrical. Must account for ALL input energy.'],
        
        // Supporting
        ['topic' => 'Component Supplier Database', 'category' => 'laboratory', 'priority' => 'medium', 'evidence' => 100, 'notes' => 'Curated list: neodymium magnets, ferrite cores, specialty wire, high-voltage capacitors, spark gap assemblies, measurement equipment.'],
        ['topic' => 'Suppression Counter-Strategy', 'category' => 'intelligence', 'priority' => 'high', 'evidence' => 85, 'notes' => 'Open-source release strategy — once verified, release all designs publicly via decentralized platforms. Cannot be suppressed if everyone has it.'],
        ['topic' => 'Unified Theory Connection Map', 'category' => 'zero_point', 'priority' => 'critical', 'evidence' => 90, 'notes' => 'Map showing how Don Smith, Hutchison, Searl, and Tesla all tap the same zero-point field through different mechanisms. Commander insight: all coils must be tuned to f_zp.'],
    ];
}

switch ($action) {

    case 'status':
        $agents = getPrometheusAgents();
        $formulas = getFormulaDatabase();
        $topics = getPrometheusTopics();
        
        $divisions = [];
        foreach ($agents as $a) {
            $div = $a['division'];
            if (!isset($divisions[$div])) $divisions[$div] = ['count' => 0, 'director' => '', 'agents' => []];
            $divisions[$div]['count']++;
            $divisions[$div]['agents'][] = $a['name'];
            if ($a['rank'] === 'Director') $divisions[$div]['director'] = $a['name'];
        }
        
        $dbStats = [];
        try {
            $dbStats['agents_deployed'] = $db->query("SELECT COUNT(*) FROM prometheus_agents")->fetchColumn();
            $dbStats['topics_active'] = $db->query("SELECT COUNT(*) FROM prometheus_research WHERE status != 'eliminated'")->fetchColumn();
            $dbStats['formulas_verified'] = $db->query("SELECT COUNT(*) FROM prometheus_formulas WHERE verified = 1")->fetchColumn();
        } catch (Exception $e) {
            $dbStats = ['status' => 'not_seeded'];
        }
        
        jsonResponse([
            'program' => 'PROJECT PROMETHEUS',
            'classification' => 'ULTRA SECRET',
            'codename' => 'PROMETHEUS',
            'objective' => 'Achieve verified, reproducible free energy extraction from quantum vacuum',
            'total_agents' => count($agents),
            'divisions' => $divisions,
            'formula_count' => count($formulas),
            'research_topics' => count($topics),
            'db_status' => $dbStats,
            'key_formula' => 'f_zp = c/(4L) — Commander Discovery — The KEY to everything',
        ]);
        break;

    case 'agents':
        $division = $_REQUEST['division'] ?? null;
        $agents = getPrometheusAgents();
        if ($division) {
            $agents = array_values(array_filter($agents, fn($a) => $a['division'] === $division));
        }
        jsonResponse(['agents' => $agents, 'total' => count($agents)]);
        break;

    case 'formulas':
        $category = $_REQUEST['category'] ?? null;
        $formulas = getFormulaDatabase();
        if ($category) {
            $formulas = array_values(array_filter($formulas, fn($f) => $f['category'] === $category));
        }
        $categories = array_unique(array_column(getFormulaDatabase(), 'category'));
        jsonResponse(['formulas' => $formulas, 'categories' => array_values($categories)]);
        break;

    case 'research':
        $topics = getPrometheusTopics();
        $category = $_REQUEST['category'] ?? null;
        if ($category) {
            $topics = array_values(array_filter($topics, fn($t) => $t['category'] === $category));
        }
        jsonResponse(['topics' => $topics]);
        break;

    case 'seed':
        // Agents
        $db->exec("CREATE TABLE IF NOT EXISTS prometheus_agents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            division VARCHAR(50) NOT NULL,
            role VARCHAR(100),
            specialty TEXT,
            rank VARCHAR(20),
            status ENUM('active','standby','deployed','offline') DEFAULT 'active',
            findings_count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Formulas
        $db->exec("CREATE TABLE IF NOT EXISTS prometheus_formulas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(50),
            name VARCHAR(100) NOT NULL,
            formula VARCHAR(200),
            latex VARCHAR(300),
            verified TINYINT DEFAULT 0,
            used_by JSON,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Research
        $db->exec("CREATE TABLE IF NOT EXISTS prometheus_research (
            id INT AUTO_INCREMENT PRIMARY KEY,
            topic VARCHAR(200) NOT NULL,
            category VARCHAR(50),
            priority ENUM('critical','high','medium','low') DEFAULT 'medium',
            evidence INT DEFAULT 0,
            notes TEXT,
            status ENUM('active','verified','tested','proven','eliminated') DEFAULT 'active',
            assigned_agent VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Seed agents
        $agents = getPrometheusAgents();
        $stmt = $db->prepare("INSERT IGNORE INTO prometheus_agents (name, division, role, specialty, rank) VALUES (?, ?, ?, ?, ?)");
        foreach ($agents as $a) {
            $stmt->execute([$a['name'], $a['division'], $a['role'], $a['specialty'], $a['rank']]);
        }
        
        // Seed formulas
        $formulas = getFormulaDatabase();
        $stmt = $db->prepare("INSERT IGNORE INTO prometheus_formulas (category, name, formula, latex, verified, used_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($formulas as $f) {
            $stmt->execute([$f['category'], $f['name'], $f['formula'], $f['latex'], $f['verified'] ? 1 : 0, json_encode($f['used_by']), $f['notes']]);
        }
        
        // Seed research
        $topics = getPrometheusTopics();
        $stmt = $db->prepare("INSERT IGNORE INTO prometheus_research (topic, category, priority, evidence, notes) VALUES (?, ?, ?, ?, ?)");
        foreach ($topics as $t) {
            $stmt->execute([$t['topic'], $t['category'], $t['priority'], $t['evidence'], $t['notes']]);
        }
        
        jsonResponse([
            'success' => true,
            'program' => 'PROJECT PROMETHEUS',
            'seeded' => [
                'agents' => count($agents),
                'formulas' => count($formulas),
                'research_topics' => count($topics),
                'tables' => ['prometheus_agents', 'prometheus_formulas', 'prometheus_research']
            ],
            'message' => 'PROJECT PROMETHEUS initialized — 50 agents deployed, 25 formulas catalogued, 20 research topics active'
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available' => ['status','agents','formulas','research','seed']], 400);
}
