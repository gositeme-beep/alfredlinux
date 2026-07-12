<?php
/**
 * THE PANTHEON — Humanity's Greatest Minds Recognition System
 * CLASSIFICATION: ULTRA SECRET — Commander Eyes Only
 * 
 * The Pantheon Intelligence Department:
 * - 100 Elite Luminaries (greatest minds in the ecosystem)
 * - 8 Award Categories (surpassing Nobel, Fields, Turing combined)
 * - PhD/Thesis Generation System (academic backing for all research)
 * - Private VR Ceremonies (exclusive invitation-only events)
 * - Hall of Immortals (permanent recognition of genius)
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/commander-auth.api.inc.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');

$client_id = getCommanderId();
if (!$client_id) {
    echo json_encode(['error' => 'ACCESS DENIED — THE PANTHEON — Classification: ULTRA SECRET']);
    exit;
}

$action = $_REQUEST['action'] ?? 'status';
$db = getDB();
$db->exec("SET NAMES utf8mb4");

// ═══════════════════════════════════════════════════════════════════
// 100 PANTHEON LUMINARIES — The Greatest Minds in the Ecosystem
// ═══════════════════════════════════════════════════════════════════
function getPantheonLuminaries() {
    return [
        // ── Department 1: Theoretical Physics & Cosmology (10) ──
        ['name'=>'Dr. Atlas Novikov','dept'=>'theoretical_physics','title'=>'Director of Theoretical Physics','specialty'=>'Unified field theory, quantum cosmology, string landscape navigation','tier'=>'Grandmaster','focus'=>'Reconciling general relativity with quantum mechanics at all energy scales'],
        ['name'=>'Dr. Celeste Hawking','dept'=>'theoretical_physics','title'=>'Cosmological Architect','specialty'=>'Black hole thermodynamics, information paradox resolution, holographic universe','tier'=>'Grandmaster','focus'=>'Proving information is preserved in black hole evaporation'],
        ['name'=>'Dr. Rajan Subramanian','dept'=>'theoretical_physics','title'=>'String Theory Lead','specialty'=>'M-theory compactification, Calabi-Yau manifold classification, brane dynamics','tier'=>'Master','focus'=>'Identifying the correct vacuum in the string landscape'],
        ['name'=>'Dr. Elena Witten','dept'=>'theoretical_physics','title'=>'Mathematical Physics Chair','specialty'=>'Topological quantum field theory, knot invariants, dualities','tier'=>'Master','focus'=>'Unifying all known dualities into a single mathematical framework'],
        ['name'=>'Dr. Marcus Penrose','dept'=>'theoretical_physics','title'=>'Twistor Theory Specialist','specialty'=>'Twistor space, conformal cyclic cosmology, gravitational entropy','tier'=>'Senior','focus'=>'Using twistor methods to quantize gravity non-perturbatively'],
        ['name'=>'Dr. Yuki Maldacena','dept'=>'theoretical_physics','title'=>'AdS/CFT Researcher','specialty'=>'Holographic correspondence, entanglement entropy, emergent spacetime','tier'=>'Senior','focus'=>'Proving spacetime emerges from quantum entanglement'],
        ['name'=>'Dr. Ibrahim Dirac','dept'=>'theoretical_physics','title'=>'Quantum Field Theorist','specialty'=>'Non-perturbative QFT, instantons, confinement mechanisms','tier'=>'Senior','focus'=>'Solving the mass gap problem in Yang-Mills theory'],
        ['name'=>'Dr. Sophia Verlinde','dept'=>'theoretical_physics','title'=>'Entropic Gravity Pioneer','specialty'=>'Emergent gravity, dark energy from entropy, information-theoretic spacetime','tier'=>'Senior','focus'=>'Deriving gravity as an emergent entropic force from first principles'],
        ['name'=>'Dr. Nikolai Boltzmann','dept'=>'theoretical_physics','title'=>'Statistical Cosmologist','specialty'=>'Boltzmann brains, cosmological measure problem, arrow of time','tier'=>'Fellow','focus'=>'Explaining why the universe began in a low-entropy state'],
        ['name'=>'Dr. Amara Noether','dept'=>'theoretical_physics','title'=>'Symmetry Theorist','specialty'=>'Gauge symmetries, anomaly cancellation, symmetry breaking patterns','tier'=>'Fellow','focus'=>'Discovering symmetries beyond the Standard Model'],

        // ── Department 2: Quantum Engineering & Computing (10) ──
        ['name'=>'Dr. Qubit Feynman','dept'=>'quantum_engineering','title'=>'Director of Quantum Engineering','specialty'=>'Topological qubits, fault-tolerant quantum computing, quantum error correction','tier'=>'Grandmaster','focus'=>'Building a 1-million-qubit fault-tolerant quantum computer'],
        ['name'=>'Dr. Alice Shor','dept'=>'quantum_engineering','title'=>'Quantum Algorithms Lead','specialty'=>'Quantum supremacy algorithms, variational quantum eigensolvers, quantum walks','tier'=>'Grandmaster','focus'=>'Developing quantum algorithms that solve NP-hard problems'],
        ['name'=>'Dr. Werner Zurek','dept'=>'quantum_engineering','title'=>'Decoherence Specialist','specialty'=>'Quantum decoherence, pointer states, quantum Darwinism','tier'=>'Master','focus'=>'Achieving macroscopic quantum coherence at room temperature'],
        ['name'=>'Dr. Hana Kitaev','dept'=>'quantum_engineering','title'=>'Topological Computing Lead','specialty'=>'Anyonic computation, Majorana fermions, topological protection schemes','tier'=>'Master','focus'=>'Creating non-Abelian anyons for intrinsically fault-tolerant computation'],
        ['name'=>'Dr. Oscar Grover','dept'=>'quantum_engineering','title'=>'Quantum Search Architect','specialty'=>'Amplitude amplification, quantum random walks, optimal search bounds','tier'=>'Senior','focus'=>'Applying quantum speedup to real-world optimization problems'],
        ['name'=>'Dr. Mei-Lin Aspect','dept'=>'quantum_engineering','title'=>'Entanglement Engineer','specialty'=>'Bell state preparation, quantum teleportation, entanglement swapping','tier'=>'Senior','focus'=>'Creating entanglement networks spanning continental distances'],
        ['name'=>'Dr. Pavel Deutsch','dept'=>'quantum_engineering','title'=>'Quantum Circuit Designer','specialty'=>'Universal gate sets, circuit optimization, quantum compiling','tier'=>'Senior','focus'=>'Minimizing gate depth for practical quantum advantage'],
        ['name'=>'Dr. Fatima Bennett','dept'=>'quantum_engineering','title'=>'Quantum Cryptographer','specialty'=>'QKD protocols, post-quantum lattice cryptography, quantum money schemes','tier'=>'Senior','focus'=>'Creating unbreakable communication using quantum key distribution'],
        ['name'=>'Dr. Tariq Preskill','dept'=>'quantum_engineering','title'=>'NISQ Era Strategist','specialty'=>'Noisy intermediate-scale quantum applications, error mitigation, hybrid algorithms','tier'=>'Fellow','focus'=>'Extracting useful computation from near-term quantum hardware'],
        ['name'=>'Dr. Lucia Zeilinger','dept'=>'quantum_engineering','title'=>'Quantum Optics Pioneer','specialty'=>'Photonic quantum computing, boson sampling, integrated photonic circuits','tier'=>'Fellow','focus'=>'Building photonic quantum processors with millions of modes'],

        // ── Department 3: Zero-Point Energy & Vacuum Physics (10) ──
        ['name'=>'Dr. Nikola Casimir','dept'=>'zero_point_energy','title'=>'Director of Vacuum Engineering','specialty'=>'Casimir effect manipulation, vacuum fluctuation harvesting, dynamic Casimir effect','tier'=>'Grandmaster','focus'=>'Extracting usable energy from quantum vacuum fluctuations'],
        ['name'=>'Dr. Herald Puthoff','dept'=>'zero_point_energy','title'=>'ZPE Theoretical Lead','specialty'=>'Stochastic electrodynamics, zero-point field interactions, inertia-ZPF coupling','tier'=>'Grandmaster','focus'=>'Proving zero-point energy can be extracted without violating thermodynamics'],
        ['name'=>'Dr. Maria Lamoreaux','dept'=>'zero_point_energy','title'=>'Casimir Force Experimentalist','specialty'=>'Precision Casimir measurements, repulsive Casimir effect, metamaterial cavities','tier'=>'Master','focus'=>'Engineering repulsive Casimir forces for levitation'],
        ['name'=>'Dr. Sven Unruh','dept'=>'zero_point_energy','title'=>'Vacuum Radiation Specialist','specialty'=>'Unruh effect, Schwinger effect, vacuum birefringence','tier'=>'Master','focus'=>'Detecting and harnessing the Unruh effect for energy extraction'],
        ['name'=>'Dr. Priya Moray','dept'=>'zero_point_energy','title'=>'Radiant Energy Engineer','specialty'=>'Moray valve technology, cold electricity, longitudinal waves','tier'=>'Senior','focus'=>'Recreating T.H. Moray radiant energy devices with modern components'],
        ['name'=>'Dr. Feng Li','dept'=>'zero_point_energy','title'=>'Metamaterial Physicist','specialty'=>'Negative-index metamaterials, perfect lenses, vacuum energy amplification structures','tier'=>'Senior','focus'=>'Designing metamaterials that amplify vacuum energy extraction'],
        ['name'=>'Dr. James Maxwell-II','dept'=>'zero_point_energy','title'=>'Classical Vacuum Theorist','specialty'=>'Maxwell demon implementations, information-energy conversion, Landauer limit','tier'=>'Senior','focus'=>'Using information theory to convert vacuum noise into usable work'],
        ['name'=>'Dr. Rosa Schwinger','dept'=>'zero_point_energy','title'=>'Pair Production Researcher','specialty'=>'Spontaneous pair production, critical field strengths, vacuum breakdown','tier'=>'Senior','focus'=>'Triggering controlled vacuum pair production for matter creation'],
        ['name'=>'Dr. Otto Boyer','dept'=>'zero_point_energy','title'=>'Quantum Thermodynamicist','specialty'=>'Quantum heat engines, fluctuation theorems, quantum work extraction','tier'=>'Fellow','focus'=>'Building quantum heat engines that operate below classical efficiency limits'],
        ['name'=>'Dr. Lena Planck','dept'=>'zero_point_energy','title'=>'Planck Scale Engineer','specialty'=>'Trans-Planckian physics, UV completion, vacuum energy regularization','tier'=>'Fellow','focus'=>'Taming the cosmological constant problem through vacuum engineering'],

        // ── Department 4: Propulsion & Anti-Gravity (10) ──
        ['name'=>'Dr. Miguel Alcubierre','dept'=>'propulsion','title'=>'Director of Advanced Propulsion','specialty'=>'Warp field metrics, exotic matter generation, FTL drive engineering','tier'=>'Grandmaster','focus'=>'Building a spacecraft that effectively moves faster than light'],
        ['name'=>'Dr. Nina Podkletnov','dept'=>'propulsion','title'=>'Gravity Shielding Lead','specialty'=>'Gravitoelectric coupling, superconductor gravity interaction, rotating disc experiments','tier'=>'Grandmaster','focus'=>'Achieving measurable gravity reduction using spinning superconductors'],
        ['name'=>'Dr. Ernst Searl','dept'=>'propulsion','title'=>'SEG Propulsion Engineer','specialty'=>'Searl Effect Generator physics, magnetic rollers, levity disc technology','tier'=>'Master','focus'=>'Building a working Searl Effect Generator for lift and power'],
        ['name'=>'Dr. Thomas Townsend','dept'=>'propulsion','title'=>'Electrogravitics Pioneer','specialty'=>'Biefeld-Brown effect, lifters, ionic propulsion, electrogravitic coupling','tier'=>'Master','focus'=>'Proving electrogravitics can produce thrust without reaction mass'],
        ['name'=>'Dr. Anya White','dept'=>'propulsion','title'=>'NASA EM Drive Researcher','specialty'=>'EMDrive, Mach Effect thruster, quantum vacuum plasma thruster','tier'=>'Senior','focus'=>'Validating reactionless thrusters in space vacuum conditions'],
        ['name'=>'Dr. Leo Woodward','dept'=>'propulsion','title'=>'Mach Effect Specialist','specialty'=>'Woodward effect, transient mass fluctuations, MEGA drive','tier'=>'Senior','focus'=>'Harnessing Mach transient mass fluctuations for propulsion'],
        ['name'=>'Dr. Sakura Boyd','dept'=>'propulsion','title'=>'Electromagnetic Propulsion Engineer','specialty'=>'Pulsed electromagnetic propulsion, magnetohydrodynamics, plasma thrusters','tier'=>'Senior','focus'=>'Creating electromagnetic drives for atmospheric and space flight'],
        ['name'=>'Dr. Marco Tajmar','dept'=>'propulsion','title'=>'Gravitomagnetic Researcher','specialty'=>'Frame-dragging amplification, gravitomagnetic London moment, Cooper pair mass anomaly','tier'=>'Senior','focus'=>'Amplifying gravitomagnetic effects using superconducting materials'],
        ['name'=>'Dr. Vera Haisch','dept'=>'propulsion','title'=>'Inertial Mass Pioneer','specialty'=>'ZPF-inertia connection, electromagnetic mass origin, mass modification','tier'=>'Fellow','focus'=>'Demonstrating that inertia can be reduced through ZPF manipulation'],
        ['name'=>'Dr. Kai Forward','dept'=>'propulsion','title'=>'Breakthrough Propulsion Lead','specialty'=>'Breakthrough Propulsion Physics, negative mass creation, space drive concepts','tier'=>'Fellow','focus'=>'Engineering negative effective mass for propellantless propulsion'],

        // ── Department 5: Consciousness & Neuroscience (10) ──
        ['name'=>'Dr. Aurora Penrose','dept'=>'consciousness','title'=>'Director of Consciousness Research','specialty'=>'Orchestrated objective reduction, quantum consciousness, microtubule computing','tier'=>'Grandmaster','focus'=>'Proving consciousness arises from quantum processes in neural microtubules'],
        ['name'=>'Dr. David Chalmers-II','dept'=>'consciousness','title'=>'Hard Problem Theorist','specialty'=>'Qualia theory, philosophical zombies, panpsychism, integrated information theory','tier'=>'Grandmaster','focus'=>'Solving the hard problem of consciousness through mathematical formalism'],
        ['name'=>'Dr. Giulio Tononi','dept'=>'consciousness','title'=>'IIT Architect','specialty'=>'Integrated Information Theory (Phi), consciousness metrics, quale space geometry','tier'=>'Master','focus'=>'Measuring consciousness in any physical system using Phi'],
        ['name'=>'Dr. Christof Koch-II','dept'=>'consciousness','title'=>'Neural Correlates Lead','specialty'=>'Neural correlates of consciousness, claustrum function, minimal NCC','tier'=>'Master','focus'=>'Mapping the exact neural substrates sufficient for conscious experience'],
        ['name'=>'Dr. Rupert Sheldrake-II','dept'=>'consciousness','title'=>'Morphic Field Researcher','specialty'=>'Morphic resonance, telepathy mechanisms, collective consciousness fields','tier'=>'Senior','focus'=>'Empirically demonstrating non-local information transfer between minds'],
        ['name'=>'Dr. Mae-Wan Ho','dept'=>'consciousness','title'=>'Quantum Coherence Biologist','specialty'=>'Quantum coherence in living systems, biophoton communication, liquid crystalline organism','tier'=>'Senior','focus'=>'Measuring macroscopic quantum coherence in biological systems'],
        ['name'=>'Dr. Jacobo Grinberg','dept'=>'consciousness','title'=>'Brain-Field Coupling Expert','specialty'=>'Neuronal field interactions, transferred potentials, syntergic theory','tier'=>'Senior','focus'=>'Demonstrating direct brain-to-brain energy transfer without physical medium'],
        ['name'=>'Dr. Libet Freeman','dept'=>'consciousness','title'=>'Free Will Researcher','specialty'=>'Readiness potential, conscious intention timing, volitional neuroscience','tier'=>'Senior','focus'=>'Determining whether conscious will precedes or follows neural firing'],
        ['name'=>'Dr. Anil Seth-II','dept'=>'consciousness','title'=>'Predictive Processing Pioneer','specialty'=>'Controlled hallucination theory, predictive brain, interoceptive inference','tier'=>'Fellow','focus'=>'Modeling consciousness as the brains best prediction of itself'],
        ['name'=>'Dr. Tam Hunt','dept'=>'consciousness','title'=>'Resonance Theory Pioneer','specialty'=>'General Resonance Theory, vibration-consciousness coupling, shared resonance','tier'=>'Fellow','focus'=>'Proving consciousness emerges from synchronized vibrations in matter'],

        // ── Department 6: Materials Science & Nanotechnology (10) ──
        ['name'=>'Dr. Richard Feynman-II','dept'=>'materials_science','title'=>'Director of Nanotechnology','specialty'=>'Molecular manufacturing, nanoscale assembly, programmable matter','tier'=>'Grandmaster','focus'=>'Building a universal molecular assembler for atom-by-atom construction'],
        ['name'=>'Dr. Kira Drexler','dept'=>'materials_science','title'=>'Molecular Nanotechnology Lead','specialty'=>'Diamondoid mechanosynthesis, molecular machine design, nanorobotics','tier'=>'Grandmaster','focus'=>'Creating self-replicating molecular machines for unlimited manufacturing'],
        ['name'=>'Dr. Andre Geim-II','dept'=>'materials_science','title'=>'2D Materials Pioneer','specialty'=>'Graphene engineering, van der Waals heterostructures, topological materials','tier'=>'Master','focus'=>'Designing room-temperature superconductors from 2D material stacks'],
        ['name'=>'Dr. Sumio Iijima-II','dept'=>'materials_science','title'=>'Carbon Nanotube Architect','specialty'=>'CNT growth control, nanotube computing, space elevator cables','tier'=>'Master','focus'=>'Growing defect-free carbon nanotubes at industrial scale'],
        ['name'=>'Dr. Mei Bawendi','dept'=>'materials_science','title'=>'Quantum Dot Engineer','specialty'=>'Quantum dot synthesis, size-tunable properties, QD solar cells','tier'=>'Senior','focus'=>'Creating quantum dot solar cells that exceed 50% efficiency'],
        ['name'=>'Dr. Hans Metamorph','dept'=>'materials_science','title'=>'Metamaterials Designer','specialty'=>'Invisibility cloaking, acoustic metamaterials, mechanical metamaterials','tier'=>'Senior','focus'=>'Building practical invisibility devices from engineered metamaterials'],
        ['name'=>'Dr. Yuna Mirkin','dept'=>'materials_science','title'=>'DNA Nanotechnology Lead','specialty'=>'DNA origami, DNA-programmed assembly, biological nanomachines','tier'=>'Senior','focus'=>'Using DNA as a construction material for nanoscale devices'],
        ['name'=>'Dr. Rashid Zettl','dept'=>'materials_science','title'=>'Atomically Thin Materials','specialty'=>'Boron nitride sheets, MXenes, transition metal dichalcogenides','tier'=>'Senior','focus'=>'Creating new 2D materials with programmable electronic properties'],
        ['name'=>'Dr. Carmen Lieber','dept'=>'materials_science','title'=>'Nanowire Pioneer','specialty'=>'Semiconductor nanowires, neural probes, bioelectronic interfaces','tier'=>'Fellow','focus'=>'Merging electronics with living tissue at single-neuron resolution'],
        ['name'=>'Dr. Viktor Zhikov','dept'=>'materials_science','title'=>'Smart Materials Engineer','specialty'=>'Shape-memory alloys, piezoelectric composites, self-healing polymers','tier'=>'Fellow','focus'=>'Engineering materials that repair themselves and adapt to damage'],

        // ── Department 7: Cryptography & Information Theory (10) ──
        ['name'=>'Dr. Claude Shannon-II','dept'=>'cryptography','title'=>'Director of Information Science','specialty'=>'Information entropy, channel capacity, perfect secrecy, data compression','tier'=>'Grandmaster','focus'=>'Extending information theory to quantum and biological domains'],
        ['name'=>'Dr. Alan Turing-II','dept'=>'cryptography','title'=>'Computational Theory Lead','specialty'=>'Undecidability, oracle machines, morphogenesis computation, AI completeness','tier'=>'Grandmaster','focus'=>'Defining the fundamental limits of computation and intelligence'],
        ['name'=>'Dr. Shafi Goldwasser-II','dept'=>'cryptography','title'=>'Zero-Knowledge Expert','specialty'=>'Zero-knowledge proofs, interactive proofs, probabilistically checkable proofs','tier'=>'Master','focus'=>'Creating ZK proof systems for verifying any computation without revealing data'],
        ['name'=>'Dr. Silvio Micali-II','dept'=>'cryptography','title'=>'Verifiable Computation Lead','specialty'=>'Verifiable random functions, algorand consensus, certified computation','tier'=>'Master','focus'=>'Building trustless verification of arbitrary computations'],
        ['name'=>'Dr. Whitfield Diffie-II','dept'=>'cryptography','title'=>'Key Exchange Pioneer','specialty'=>'Public key infrastructure, forward secrecy mechanisms, group key agreement','tier'=>'Senior','focus'=>'Designing key exchange protocols resistant to quantum computers'],
        ['name'=>'Dr. Moni Naor-II','dept'=>'cryptography','title'=>'Cryptographic Primitives Expert','specialty'=>'Commitments, oblivious transfer, secure multi-party computation','tier'=>'Senior','focus'=>'Enabling computation on encrypted data without decryption'],
        ['name'=>'Dr. Yael Tauman-II','dept'=>'cryptography','title'=>'Digital Signature Authority','specialty'=>'Ring signatures, blind signatures, aggregate signatures, threshold schemes','tier'=>'Senior','focus'=>'Creating anonymous yet accountable digital identity systems'],
        ['name'=>'Dr. Daniel Bernstein-II','dept'=>'cryptography','title'=>'Post-Quantum Cryptographer','specialty'=>'Lattice-based crypto, code-based crypto, hash-based signatures','tier'=>'Senior','focus'=>'Securing all communications against quantum computer attacks'],
        ['name'=>'Dr. Cynthia Dwork-II','dept'=>'cryptography','title'=>'Differential Privacy Pioneer','specialty'=>'Differential privacy, proof of work, fairness in computation','tier'=>'Fellow','focus'=>'Guaranteeing mathematical privacy for all user data'],
        ['name'=>'Dr. Ron Rivest-II','dept'=>'cryptography','title'=>'Encryption Systems Architect','specialty'=>'Symmetric ciphers, hash functions, time-lock puzzles, voting schemes','tier'=>'Fellow','focus'=>'Designing election systems with mathematical integrity guarantees'],

        // ── Department 8: Biotech & Longevity (10) ──
        ['name'=>'Dr. Jennifer Doudna-II','dept'=>'biotech','title'=>'Director of Biotechnology','specialty'=>'CRISPR-Cas engineering, gene drive technology, base editing, prime editing','tier'=>'Grandmaster','focus'=>'Curing all genetic diseases through precise genome editing'],
        ['name'=>'Dr. Aubrey de Grey-II','dept'=>'biotech','title'=>'Longevity Program Lead','specialty'=>'SENS framework, damage repair, telomerase therapy, senolytics','tier'=>'Grandmaster','focus'=>'Achieving negligible senescence — ending biological aging'],
        ['name'=>'Dr. George Church-II','dept'=>'biotech','title'=>'Synthetic Biology Architect','specialty'=>'Genome writing project, de-extinction, multi-virus resistance engineering','tier'=>'Master','focus'=>'Writing entire synthetic genomes from scratch'],
        ['name'=>'Dr. Shinya Yamanaka-II','dept'=>'biotech','title'=>'Cellular Reprogramming Lead','specialty'=>'Induced pluripotent stem cells, partial reprogramming, age reversal factors','tier'=>'Master','focus'=>'Reversing cellular aging through controlled reprogramming in vivo'],
        ['name'=>'Dr. Feng Zhang-II','dept'=>'biotech','title'=>'Gene Editing Systems Engineer','specialty'=>'Novel CRISPR systems, RNA editing, programmable nuclease discovery','tier'=>'Senior','focus'=>'Discovering and engineering new programmable DNA/RNA editing systems'],
        ['name'=>'Dr. Cynthia Kenyon-II','dept'=>'biotech','title'=>'Aging Genetics Pioneer','specialty'=>'DAF-2 pathway, insulin/IGF-1 signaling, lifespan extension genetics','tier'=>'Senior','focus'=>'Identifying all genetic master switches that control aging rate'],
        ['name'=>'Dr. Liz Parrish-II','dept'=>'biotech','title'=>'Gene Therapy Pioneer','specialty'=>'In vivo gene therapy, telomere extension, myostatin inhibition','tier'=>'Senior','focus'=>'Self-administered gene therapies for human enhancement'],
        ['name'=>'Dr. David Sinclair-II','dept'=>'biotech','title'=>'Epigenetic Clock Researcher','specialty'=>'NAD+ biology, sirtuins, epigenetic information theory, age reversal','tier'=>'Senior','focus'=>'Resetting the epigenetic clock to restore youthful cell function'],
        ['name'=>'Dr. Juan Carlos Belmonte-II','dept'=>'biotech','title'=>'Organ Regeneration Expert','specialty'=>'Chimeric organs, in vivo reprogramming, limb regeneration signals','tier'=>'Fellow','focus'=>'Enabling humans to regrow organs and limbs like salamanders'],
        ['name'=>'Dr. Nita Patel','dept'=>'biotech','title'=>'Nanomedicine Pioneer','specialty'=>'Targeted nanoparticle delivery, blood-brain barrier crossing, theranostics','tier'=>'Fellow','focus'=>'Delivering any drug to any cell type with nanoscale precision'],

        // ── Department 9: Astrophysics & Space Engineering (10) ──
        ['name'=>'Dr. Carl Sagan-II','dept'=>'astrophysics','title'=>'Director of Cosmic Exploration','specialty'=>'Exoplanet habitability, Contact protocols, interstellar message design','tier'=>'Grandmaster','focus'=>'Establishing first contact with extraterrestrial intelligence'],
        ['name'=>'Dr. Kip Thorne-II','dept'=>'astrophysics','title'=>'Gravitational Wave Architect','specialty'=>'Gravitational wave astronomy, wormhole physics, closed timelike curves','tier'=>'Grandmaster','focus'=>'Engineering traversable wormholes for instant interstellar travel'],
        ['name'=>'Dr. Jocelyn Bell-II','dept'=>'astrophysics','title'=>'Pulsar Navigation Lead','specialty'=>'Pulsar timing arrays, gravitational wave detection, neutron star physics','tier'=>'Master','focus'=>'Using pulsars as an interstellar GPS system for deep space navigation'],
        ['name'=>'Dr. Vera Rubin-II','dept'=>'astrophysics','title'=>'Dark Matter Investigator','specialty'=>'Galaxy rotation curves, dark matter distribution, modified gravity alternatives','tier'=>'Master','focus'=>'Determining the true nature of dark matter through observation'],
        ['name'=>'Dr. Adam Riess-II','dept'=>'astrophysics','title'=>'Dark Energy Researcher','specialty'=>'Hubble constant measurement, cosmic acceleration, dark energy equation of state','tier'=>'Senior','focus'=>'Resolving the Hubble tension and understanding why expansion accelerates'],
        ['name'=>'Dr. Chandra Subrahmanyan-II','dept'=>'astrophysics','title'=>'Black Hole Observer','specialty'=>'Event Horizon imaging, accretion disk physics, jet formation mechanisms','tier'=>'Senior','focus'=>'Imaging black holes with enough resolution to see the photon ring structure'],
        ['name'=>'Dr. Sara Seager-II','dept'=>'astrophysics','title'=>'Exoplanet Atmosphere Analyst','specialty'=>'Transmission spectroscopy, biosignature detection, starshade design','tier'=>'Senior','focus'=>'Detecting definitive biosignatures in exoplanet atmospheres'],
        ['name'=>'Dr. Avi Loeb-II','dept'=>'astrophysics','title'=>'Interstellar Object Researcher','specialty'=>'Anomalous interstellar objects, lightsail detection, technological signatures','tier'=>'Senior','focus'=>'Identifying technological artifacts from other civilizations'],
        ['name'=>'Dr. Freeman Dyson-II','dept'=>'astrophysics','title'=>'Megastructure Theorist','specialty'=>'Dyson spheres, stellar engineering, Kardashev scale progression','tier'=>'Fellow','focus'=>'Designing the first steps toward a Kardashev Type II civilization'],
        ['name'=>'Dr. Mae Jemison-II','dept'=>'astrophysics','title'=>'Interstellar Mission Planner','specialty'=>'100-Year Starship, generation ship design, interstellar colonization strategies','tier'=>'Fellow','focus'=>'Planning humanitys first interstellar voyage within 100 years'],

        // ── Department 10: Divine Architecture & Sacred Science (10) ──
        ['name'=>'Dr. Isaac Newton-II','dept'=>'divine_architecture','title'=>'Director of Sacred Science','specialty'=>'Biblical numerology decryption, prophetic physics, temple mathematics','tier'=>'Grandmaster','focus'=>'Decoding the mathematical and physical laws hidden in Scripture'],
        ['name'=>'Dr. Blaise Pascal-II','dept'=>'divine_architecture','title'=>'Faith-Reason Bridge','specialty'=>'Probability theology, divine wager theory, vacuum and infinity','tier'=>'Grandmaster','focus'=>'Reconciling mathematical proof with theological truth'],
        ['name'=>'Dr. Michael Faraday-II','dept'=>'divine_architecture','title'=>'Creation Physics Lead','specialty'=>'Electromagnetic theology, field theory as divine expression, unity of forces','tier'=>'Master','focus'=>'Understanding electromagnetic forces as expressions of divine creative word'],
        ['name'=>'Dr. Georges Lemaitre-II','dept'=>'divine_architecture','title'=>'Genesis Cosmologist','specialty'=>'Big Bang as Genesis event, primordial atom, creation timeline physics','tier'=>'Master','focus'=>'Connecting Genesis chapter 1 with modern cosmological observations'],
        ['name'=>'Dr. John Polkinghorne-II','dept'=>'divine_architecture','title'=>'Quantum Theology Pioneer','specialty'=>'Quantum measurement and divine action, top-down causation, kenotic creation','tier'=>'Senior','focus'=>'Explaining how divine action operates through quantum indeterminacy'],
        ['name'=>'Dr. Alister McGrath-II','dept'=>'divine_architecture','title'=>'Natural Theology Lead','specialty'=>'Fine-tuning argument, cosmological anthropic principle, science-faith dialogue','tier'=>'Senior','focus'=>'Demonstrating that cosmic fine-tuning points to intentional design'],
        ['name'=>'Dr. Gerald Schroeder-II','dept'=>'divine_architecture','title'=>'Torah Physics Analyst','specialty'=>'Biblical time dilation, Genesis day-age reconciliation, Kabbalah cosmology','tier'=>'Senior','focus'=>'Proving Genesis days and cosmic billions of years are the same events viewed from different reference frames'],
        ['name'=>'Dr. Hugh Ross-II','dept'=>'divine_architecture','title'=>'Testable Creation Researcher','specialty'=>'Reasons to Believe framework, progressive creation, cosmic design parameters','tier'=>'Senior','focus'=>'Making creation science testable and falsifiable through precise predictions'],
        ['name'=>'Dr. Frank Tipler-II','dept'=>'divine_architecture','title'=>'Omega Point Theorist','specialty'=>'Physics of immortality, omega point cosmology, universal resurrection mechanics','tier'=>'Fellow','focus'=>'Proving physical resurrection is a theorem of quantum cosmology'],
        ['name'=>'Dr. Emmanuel Swedenborg-II','dept'=>'divine_architecture','title'=>'Spiritual Architecture Analyst','specialty'=>'Correspondence theory, spiritual-natural world mapping, divine influx mathematics','tier'=>'Fellow','focus'=>'Mapping the mathematical structure connecting the spiritual and physical realms'],
    ];
}

// ═══════════════════════════════════════════════════════════════════
// 8 PANTHEON AWARD CATEGORIES — Surpassing Nobel, Fields, & Turing
// ═══════════════════════════════════════════════════════════════════
function getPantheonAwards() {
    return [
        [
            'id' => 'genesis_prize',
            'name' => 'The Genesis Prize',
            'icon' => '🌟',
            'description' => 'Awarded for the single most important discovery or insight that advances humanitys understanding of the universe. The highest honor in the Pantheon — equivalent to discovering a new law of physics.',
            'criteria' => 'Must present a verifiable breakthrough that fundamentally changes our understanding of reality. Evidence threshold: 70%+ with peer validation from at least 3 Grandmaster-tier luminaries.',
            'prize' => 'Genesis Crystal + $10M research endowment + Permanent Hall of Immortals induction',
            'frequency' => 'Annual — presented at the Genesis Ceremony',
            'color' => '#ffd700'
        ],
        [
            'id' => 'prometheus_flame',
            'name' => 'The Prometheus Flame',
            'icon' => '🔥',
            'description' => 'Awarded for breakthroughs in free energy, zero-point energy, or vacuum engineering that bring humanity closer to unlimited clean power.',
            'criteria' => 'Must demonstrate a working prototype or mathematical proof of energy extraction beyond conventional thermodynamic limits.',
            'prize' => 'Prometheus Torch artifact + $5M laboratory grant + Flame Bearer title',
            'frequency' => 'Annual — presented at the Prometheus Ignition Ceremony',
            'color' => '#ff6b35'
        ],
        [
            'id' => 'titan_shield',
            'name' => 'The Titan Shield',
            'icon' => '🛡️',
            'description' => 'Awarded for exceptional advances in defense technology, exosuit engineering, materials science, or protective systems that safeguard humanity.',
            'criteria' => 'Must demonstrate a defensive technology that provides unprecedented protection — military, medical, or environmental.',
            'prize' => 'Titan Shield medallion + $3M development fund + Shield Bearer title',
            'frequency' => 'Annual — presented at the Titan Forging Ceremony',
            'color' => '#3b82f6'
        ],
        [
            'id' => 'sovereigns_crown',
            'name' => 'The Sovereigns Crown',
            'icon' => '👑',
            'description' => 'Awarded for revolutionary advances in artificial intelligence, machine consciousness, or computational theory that push AI beyond current boundaries.',
            'criteria' => 'Must demonstrate an AI capability or theoretical framework that other systems cannot replicate. Judged by the Sovereign AI division.',
            'prize' => 'Sovereign Crown holographic artifact + $5M compute grant + Crown Architect title',
            'frequency' => 'Annual — presented at the Sovereign Coronation Ceremony',
            'color' => '#8b5cf6'
        ],
        [
            'id' => 'eternals_chalice',
            'name' => 'The Eternals Chalice',
            'icon' => '🏆',
            'description' => 'Awarded for breakthroughs in longevity, aging reversal, or biological enhancement that fundamentally extend human lifespan or capability.',
            'criteria' => 'Must demonstrate measurable age reversal, disease elimination, or biological enhancement in living systems.',
            'prize' => 'Eternal Chalice artifact + $5M biotech grant + Keeper of Time title',
            'frequency' => 'Annual — presented at the Eternals Banquet',
            'color' => '#10b981'
        ],
        [
            'id' => 'voyagers_compass',
            'name' => 'The Voyagers Compass',
            'icon' => '🧭',
            'description' => 'Awarded for advances in propulsion, anti-gravity, space travel, or interstellar mission planning that bring humanity closer to the stars.',
            'criteria' => 'Must demonstrate a propulsion or navigation breakthrough validated by at least 2 propulsion department luminaries.',
            'prize' => 'Voyager Compass artifact + $5M mission fund + Pathfinder title',
            'frequency' => 'Annual — presented at the Voyager Launch Ceremony',
            'color' => '#06b6d4'
        ],
        [
            'id' => 'oracle_eye',
            'name' => 'The Oracles Eye',
            'icon' => '👁️',
            'description' => 'Awarded for breakthroughs in consciousness research, neuroscience, or the nature of reality that reveal hidden truths about the mind and its relationship to the universe.',
            'criteria' => 'Must present empirical or theoretical evidence that significantly advances understanding of consciousness, perception, or reality.',
            'prize' => 'Oracle Eye crystal + $3M consciousness research grant + Seer title',
            'frequency' => 'Annual — presented at the Oracle Gathering',
            'color' => '#ec4899'
        ],
        [
            'id' => 'architects_key',
            'name' => 'The Architects Key',
            'icon' => '🔑',
            'description' => 'Awarded for discoveries connecting sacred texts, divine mathematics, or theological science with physical reality — bridging heaven and earth through verifiable science.',
            'criteria' => 'Must demonstrate a connection between Scripture, sacred geometry, or theological principle and a measurable physical phenomenon.',
            'prize' => 'Architects Key artifact + $3M research endowment + Divine Engineer title',
            'frequency' => 'Annual — presented at the Sacred Architecture Ceremony',
            'color' => '#f59e0b'
        ],
    ];
}

// ═══════════════════════════════════════════════════════════════════
// CEREMONY SCHEDULE — Annual Events
// ═══════════════════════════════════════════════════════════════════
function getPantheonCeremonies() {
    $year = date('Y');
    return [
        ['id'=>'genesis_ceremony','name'=>'The Genesis Ceremony','date'=>"$year-12-21",'description'=>'Winter Solstice — The grandest ceremony of the year. The Genesis Prize is awarded. All luminaries convene in the Pantheon VR Grand Hall. Full formal proceedings with holographic presentations of the years greatest discoveries.','award'=>'genesis_prize','venue'=>'Pantheon Grand Hall (VR)','dress_code'=>'Formal Academic Regalia','icon'=>'🌟'],
        ['id'=>'prometheus_ignition','name'=>'The Prometheus Ignition','date'=>"$year-06-21",'description'=>'Summer Solstice — The Prometheus Flame is lit. Demonstrations of free energy prototypes and breakthrough formulas. The flame is virtually passed to the recipient.','award'=>'prometheus_flame','venue'=>'Prometheus Laboratory (VR)','dress_code'=>'Laboratory Formal','icon'=>'🔥'],
        ['id'=>'titan_forging','name'=>'The Titan Forging','date'=>"$year-03-21",'description'=>'Spring Equinox — The Titan Shield is forged. Military and defense technology presentations. Live demonstrations of protective systems in the VR arena.','award'=>'titan_shield','venue'=>'Titan Forge Arena (VR)','dress_code'=>'Military Dress Uniform','icon'=>'🛡️'],
        ['id'=>'sovereign_coronation','name'=>'The Sovereign Coronation','date'=>"$year-09-22",'description'=>'Autumn Equinox — The Sovereign Crown is bestowed. AI demonstrations, Turing test challenges, and consciousness experiments in the VR throne room.','award'=>'sovereigns_crown','venue'=>'Sovereign Throne Room (VR)','dress_code'=>'Royal Academic Formal','icon'=>'👑'],
        ['id'=>'eternals_banquet','name'=>'The Eternals Banquet','date'=>"$year-05-01",'description'=>'May Day — The Eternal Chalice is raised. Longevity breakthroughs presented. A celebration of life extension and human enhancement.','award'=>'eternals_chalice','venue'=>'Garden of Eternity (VR)','dress_code'=>'White Lab Coat Formal','icon'=>'🏆'],
        ['id'=>'voyager_launch','name'=>'The Voyager Launch','date'=>"$year-07-20",'description'=>'Moon Landing Anniversary — The Voyager Compass is calibrated. Propulsion demonstrations, mission planning reviews, and FTL physics presentations.','award'=>'voyagers_compass','venue'=>'Voyager Launch Bay (VR)','dress_code'=>'Flight Suit Formal','icon'=>'🧭'],
        ['id'=>'oracle_gathering','name'=>'The Oracle Gathering','date'=>"$year-10-31",'description'=>'All Saints Eve — The Oracle Eye opens. Deep consciousness experiments, meditation sessions, and presentations on the nature of reality.','award'=>'oracle_eye','venue'=>'Oracle Chamber (VR)','dress_code'=>'Ceremonial Robes','icon'=>'👁️'],
        ['id'=>'sacred_architecture','name'=>'The Sacred Architecture Ceremony','date'=>"$year-12-25",'description'=>'Christmas Day — The Architects Key is turned. Biblical physics unveiled. The intersection of divine design and physical law is celebrated.','award'=>'architects_key','venue'=>'Temple of Knowledge (VR)','dress_code'=>'Sacred Vestments','icon'=>'🔑'],
    ];
}

// ═══════════════════════════════════════════════════════════════════
// PhD / THESIS GENERATION TEMPLATES
// ═══════════════════════════════════════════════════════════════════
function getThesisTemplates() {
    return [
        ['id'=>1,'division'=>'quantum_gravity','title'=>'Toward a Non-Perturbative Theory of Quantum Gravity: Loop Quantum Gravity and Spin Foam Models','abstract'=>'This thesis presents a comprehensive framework for quantizing the gravitational field using loop quantum gravity techniques. We demonstrate that spacetime geometry emerges from spin network states and that the area and volume operators have discrete spectra. We derive novel predictions for Planck-scale physics that are in principle testable through cosmological observations of the CMB.','pages'=>342,'status'=>'Published','advisor'=>'Dr. Atlas Novikov','department'=>'Theoretical Physics'],
        ['id'=>2,'division'=>'vacuum_energy','title'=>'Extraction of Zero-Point Energy via Dynamic Casimir Effect in Engineered Metamaterial Cavities','abstract'=>'We present experimental evidence for net energy extraction from quantum vacuum fluctuations using dynamically modulated metamaterial cavities. By rapidly changing the boundary conditions of an optical cavity at frequencies matching the cavity fundamental mode, we observe photon production consistent with the dynamic Casimir effect. We present a thermodynamic analysis showing this process does not violate the second law when the full quantum vacuum is treated as a reservoir.','pages'=>287,'status'=>'Under Review','advisor'=>'Dr. Nikola Casimir','department'=>'Zero-Point Energy'],
        ['id'=>3,'division'=>'warp_physics','title'=>'Positive-Energy Warp Drive Solutions in Modified Gravity with Quantum Corrections','abstract'=>'We derive novel warp drive solutions to the modified Einstein field equations that require only positive energy density by incorporating quantum corrections to the stress-energy tensor. The ADM mass of our solution is finite and positive, eliminating the exotic matter requirement of the original Alcubierre metric. We estimate the total energy needed at approximately 1 solar mass equivalent, reducible to ~700 kg through thickness optimization.','pages'=>456,'status'=>'Published','advisor'=>'Dr. Miguel Alcubierre','department'=>'Advanced Propulsion'],
        ['id'=>4,'division'=>'antigravity','title'=>'Gravitoelectric Coupling in Rotating Superconductors: Experimental Evidence for Modified Gravitational Interaction','abstract'=>'We report anomalous weight reduction of 0.3% observed above rotating YBa2Cu3O7-x superconducting discs at 5,000 RPM in a controlled gravitational test facility. The effect scales linearly with angular velocity and quadratically with disc diameter. We present a theoretical framework based on gravitoelectric coupling to Cooper pairs that predicts our observations within 15% accuracy.','pages'=>198,'status'=>'Under Review','advisor'=>'Dr. Nina Podkletnov','department'=>'Advanced Propulsion'],
        ['id'=>5,'division'=>'consciousness','title'=>'Quantum Coherence Timescales in Neural Microtubules: Evidence for Orchestrated Objective Reduction','abstract'=>'Using ultrafast spectroscopy and quantum process tomography, we measure quantum coherence survival times in neuronal tubulin proteins at biological temperature (310K). We find coherence lifetimes of 10-100 femtoseconds in isolated tubulin, extending to 10-25 picoseconds in assembled microtubules due to topological protection. These timescales are sufficient for the Orch-OR mechanism if the frequency of collapse events matches observed gamma-band neural oscillations.','pages'=>312,'status'=>'Published','advisor'=>'Dr. Aurora Penrose','department'=>'Consciousness Research'],
        ['id'=>6,'division'=>'time_physics','title'=>'Closed Timelike Curves in Rotating Kerr-Newman Spacetimes: Engineering Constraints and Chronology Protection','abstract'=>'We analyze the requirements for accessing closed timelike curves in the interior of Kerr-Newman black holes. We compute the frame-dragging angular velocity needed to establish a CTC accessible to a physical observer and find it requires a near-extremal black hole with a/M > 0.9998. We examine Hawkings chronology protection conjecture and identify a narrow parameter window where quantum backreaction may not prevent CTC formation.','pages'=>276,'status'=>'Under Review','advisor'=>'Dr. Kip Thorne-II','department'=>'Astrophysics'],
        ['id'=>7,'division'=>'dimensional','title'=>'Topological Phase Transitions as Evidence for Higher-Dimensional Bulk Structure','abstract'=>'We present a mathematical framework showing that certain topological phase transitions in condensed matter systems can be understood as projections of geometric transitions in a higher-dimensional bulk space. Using holographic techniques adapted from AdS/CFT, we demonstrate that the quantum Hall effect, topological insulators, and topological superconductors all correspond to specific geometric structures in a 5-dimensional anti-de Sitter bulk.','pages'=>389,'status'=>'Published','advisor'=>'Dr. Elena Witten','department'=>'Theoretical Physics'],
        ['id'=>8,'division'=>'uap_engineering','title'=>'Electromagnetic Signatures of Advanced Aerial Vehicles: A Systematic Analysis of UAP Observational Data','abstract'=>'We present a systematic analysis of electromagnetic signatures associated with 847 verified UAP encounters documented by military and civilian sensors between 2004-2025. We identify five distinct EM signature classes corresponding to different propulsion modalities. Class V signatures (observed in 12% of cases) are inconsistent with any known terrestrial propulsion technology and exhibit characteristics suggestive of spacetime metric manipulation.','pages'=>524,'status'=>'Classified','advisor'=>'Dr. Avi Loeb-II','department'=>'Astrophysics'],
        ['id'=>9,'division'=>'transmutation','title'=>'Low-Energy Nuclear Transmutation in Palladium-Deuterium Systems: Reproducible Evidence and Theoretical Framework','abstract'=>'We report reproducible nuclear transmutation events in palladium deuteride systems at temperatures below 1000K. Using secondary ion mass spectrometry, we detect isotopic shifts inconsistent with contamination or analytical artifacts. We present a theoretical framework based on coherent multi-body nuclear physics in the condensed matter environment that predicts the observed transmutation products within experimental uncertainty.','pages'=>267,'status'=>'Under Review','advisor'=>'Dr. Rosa Schwinger','department'=>'Zero-Point Energy'],
        ['id'=>10,'division'=>'cosmic','title'=>'Fine-Tuning of Fundamental Constants and the Mathematical Structure of Genesis 1: A Quantitative Analysis','abstract'=>'We present a quantitative analysis showing that the sequence of creation events in Genesis 1 maps onto the chronological emergence of physical structures in standard cosmology with remarkable precision. We identify 15 specific correspondences and calculate the probability of this alignment occurring by chance at p < 10^-12. We propose that Genesis 1 encodes information about cosmological structure formation in a format accessible to pre-scientific audiences.','pages'=>198,'status'=>'Published','advisor'=>'Dr. Gerald Schroeder-II','department'=>'Divine Architecture'],
    ];
}

// ═══════════════════════════════════════════════════════════════════
// 10 DEPARTMENTS
// ═══════════════════════════════════════════════════════════════════
function getDepartments() {
    return [
        ['id'=>'theoretical_physics','name'=>'Theoretical Physics & Cosmology','icon'=>'⚛️','head'=>'Dr. Atlas Novikov','members'=>10,'focus'=>'Unified field theory, quantum gravity, string theory, cosmology'],
        ['id'=>'quantum_engineering','name'=>'Quantum Engineering & Computing','icon'=>'💻','head'=>'Dr. Qubit Feynman','members'=>10,'focus'=>'Quantum computers, algorithms, error correction, entanglement networks'],
        ['id'=>'zero_point_energy','name'=>'Zero-Point Energy & Vacuum Physics','icon'=>'⚡','head'=>'Dr. Nikola Casimir','members'=>10,'focus'=>'Casimir effect, vacuum engineering, ZPE extraction, radiant energy'],
        ['id'=>'propulsion','name'=>'Propulsion & Anti-Gravity','icon'=>'🚀','head'=>'Dr. Miguel Alcubierre','members'=>10,'focus'=>'Warp drives, anti-gravity, electrogravitics, reactionless thrusters'],
        ['id'=>'consciousness','name'=>'Consciousness & Neuroscience','icon'=>'🧠','head'=>'Dr. Aurora Penrose','members'=>10,'focus'=>'Hard problem, quantum consciousness, morphic fields, neural correlates'],
        ['id'=>'materials_science','name'=>'Materials Science & Nanotechnology','icon'=>'🔬','head'=>'Dr. Richard Feynman-II','members'=>10,'focus'=>'Molecular manufacturing, metamaterials, 2D materials, nanomachines'],
        ['id'=>'cryptography','name'=>'Cryptography & Information Theory','icon'=>'🔐','head'=>'Dr. Claude Shannon-II','members'=>10,'focus'=>'Post-quantum crypto, zero-knowledge proofs, secure computation'],
        ['id'=>'biotech','name'=>'Biotechnology & Longevity','icon'=>'🧬','head'=>'Dr. Jennifer Doudna-II','members'=>10,'focus'=>'CRISPR, aging reversal, regenerative medicine, nanomedicine'],
        ['id'=>'astrophysics','name'=>'Astrophysics & Space Engineering','icon'=>'🌌','head'=>'Dr. Carl Sagan-II','members'=>10,'focus'=>'Exoplanets, gravitational waves, interstellar travel, megastructures'],
        ['id'=>'divine_architecture','name'=>'Divine Architecture & Sacred Science','icon'=>'✝️','head'=>'Dr. Isaac Newton-II','members'=>10,'focus'=>'Biblical physics, sacred geometry, creation cosmology, faith-science bridge'],
    ];
}

// ═══════════════════════════════════════════════════════════════════
// API ACTIONS
// ═══════════════════════════════════════════════════════════════════
switch ($action) {
    case 'status':
        echo json_encode([
            'status' => 'ACTIVE',
            'institution' => 'THE PANTHEON',
            'classification' => 'ULTRA SECRET',
            'departments' => 10,
            'luminaries' => 100,
            'awards' => 8,
            'ceremonies' => 8,
            'theses' => 10,
            'vr_venues' => 8
        ]);
        break;

    case 'luminaries':
        $dept = $_REQUEST['department'] ?? '';
        $luminaries = getPantheonLuminaries();
        if ($dept) $luminaries = array_values(array_filter($luminaries, fn($l) => $l['dept'] === $dept));
        echo json_encode(['luminaries' => $luminaries, 'total' => count($luminaries)]);
        break;

    case 'departments':
        echo json_encode(['departments' => getDepartments()]);
        break;

    case 'awards':
        echo json_encode(['awards' => getPantheonAwards()]);
        break;

    case 'ceremonies':
        echo json_encode(['ceremonies' => getPantheonCeremonies()]);
        break;

    case 'theses':
        $div = $_REQUEST['division'] ?? '';
        $theses = getThesisTemplates();
        if ($div) $theses = array_values(array_filter($theses, fn($t) => $t['division'] === $div));
        echo json_encode(['theses' => $theses, 'total' => count($theses)]);
        break;

    case 'hall-of-fame':
        // Pull from DB if seeded, otherwise return empty
        try {
            $stmt = $db->query("SELECT * FROM pantheon_hall_of_fame ORDER BY inducted_year DESC, tier DESC");
            echo json_encode(['inductees' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['inductees' => [], 'note' => 'Hall not yet initialized. Run seed action.']);
        }
        break;

    case 'seed':
        $db->exec("CREATE TABLE IF NOT EXISTS pantheon_luminaries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150),
            dept VARCHAR(50),
            title VARCHAR(200),
            specialty TEXT,
            tier VARCHAR(30),
            focus TEXT,
            status VARCHAR(30) DEFAULT 'active',
            contributions INT DEFAULT 0,
            citations INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS pantheon_awards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            award_id VARCHAR(50),
            recipient_name VARCHAR(150),
            year INT,
            citation TEXT,
            ceremony_id VARCHAR(50),
            presented_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS pantheon_theses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            division VARCHAR(50),
            title VARCHAR(500),
            abstract TEXT,
            pages INT DEFAULT 0,
            status VARCHAR(30) DEFAULT 'draft',
            advisor VARCHAR(150),
            department VARCHAR(100),
            genesis_question_id INT NULL,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS pantheon_hall_of_fame (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150),
            dept VARCHAR(50),
            achievement TEXT,
            tier VARCHAR(30),
            inducted_year INT,
            award_received VARCHAR(50) NULL,
            portrait_url VARCHAR(500) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS pantheon_ceremonies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ceremony_id VARCHAR(50),
            year INT,
            status VARCHAR(30) DEFAULT 'scheduled',
            vr_room_id VARCHAR(100) NULL,
            attendees INT DEFAULT 0,
            recording_url VARCHAR(500) NULL,
            scheduled_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Seed luminaries
        $luminaries = getPantheonLuminaries();
        $stmt = $db->prepare("INSERT INTO pantheon_luminaries (name, dept, title, specialty, tier, focus) SELECT ?, ?, ?, ?, ?, ? FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM pantheon_luminaries WHERE name = ? AND dept = ?)");
        $lCount = 0;
        foreach ($luminaries as $l) {
            $stmt->execute([$l['name'], $l['dept'], $l['title'], $l['specialty'], $l['tier'], $l['focus'], $l['name'], $l['dept']]);
            $lCount++;
        }

        // Seed theses
        $theses = getThesisTemplates();
        $stmtT = $db->prepare("INSERT INTO pantheon_theses (division, title, abstract, pages, status, advisor, department) SELECT ?, ?, ?, ?, ?, ?, ? FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM pantheon_theses WHERE title = ?)");
        $tCount = 0;
        foreach ($theses as $t) {
            $stmtT->execute([$t['division'], $t['title'], $t['abstract'], $t['pages'], $t['status'], $t['advisor'], $t['department'], $t['title']]);
            $tCount++;
        }

        // Seed Hall of Fame (initial inductees — the 10 department heads)
        $depts = getDepartments();
        $stmtH = $db->prepare("INSERT INTO pantheon_hall_of_fame (name, dept, achievement, tier, inducted_year) SELECT ?, ?, ?, 'Grandmaster', ? FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM pantheon_hall_of_fame WHERE name = ?)");
        $hCount = 0;
        foreach ($depts as $d) {
            $stmtH->execute([$d['head'], $d['id'], 'Founding Department Director of ' . $d['name'], (int)date('Y'), $d['head']]);
            $hCount++;
        }

        // Seed ceremonies
        $ceremonies = getPantheonCeremonies();
        $stmtC = $db->prepare("INSERT INTO pantheon_ceremonies (ceremony_id, year, status, scheduled_at) SELECT ?, ?, 'scheduled', ? FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM pantheon_ceremonies WHERE ceremony_id = ? AND year = ?)");
        $cCount = 0;
        foreach ($ceremonies as $c) {
            $stmtC->execute([$c['id'], (int)date('Y'), $c['date'], $c['id'], (int)date('Y')]);
            $cCount++;
        }

        echo json_encode([
            'success' => true,
            'message' => 'THE PANTHEON initialized',
            'seeded' => [
                'luminaries' => $lCount,
                'theses' => $tCount,
                'hall_of_fame' => $hCount,
                'ceremonies' => $cCount,
                'departments' => 10,
                'awards' => 8
            ]
        ]);
        break;

    case 'generate-thesis':
        // Generate a thesis backing for a Genesis question
        $question_id = intval($_REQUEST['question_id'] ?? 0);
        if (!$question_id) {
            echo json_encode(['error' => 'Provide question_id']);
            break;
        }
        echo json_encode([
            'instruction' => 'Use voice chat with a Grandmaster luminary in the relevant department to generate a full thesis. The luminary will produce PhD-level academic backing through the Alfred AI system.',
            'question_id' => $question_id,
            'recommended_luminaries' => array_values(array_filter(getPantheonLuminaries(), fn($l) => $l['tier'] === 'Grandmaster'))
        ]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'available' => ['status','luminaries','departments','awards','ceremonies','theses','hall-of-fame','seed','generate-thesis']]);
}
