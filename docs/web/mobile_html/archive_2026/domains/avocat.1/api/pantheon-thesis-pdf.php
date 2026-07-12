<?php
/**
 * Pantheon Thesis PDF Generator
 * Generates real multi-page academic PDF documents for each thesis.
 * Uses wkhtmltopdf to convert HTML → PDF.
 * 
 * CLASSIFICATION: ULTRA SECRET — Commander Eyes Only
 * 
 * Usage: /api/pantheon-thesis-pdf.php?id=1
 *        /api/pantheon-thesis-pdf.php?id=1&action=generate  (force regenerate)
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/commander-auth.api.inc.php';

$client_id = getCommanderId();
if (!$client_id) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ACCESS DENIED']);
    exit;
}

$thesisId = (int)($_GET['id'] ?? 0);
$action = ($_GET['action'] ?? 'download') === 'generate' ? 'generate' : 'download';

if ($thesisId < 1 || $thesisId > 10) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid thesis ID (1-10)']);
    exit;
}

// ── Get thesis data ──
$theses = getThesisData();
$thesis = $theses[$thesisId] ?? null;

if (!$thesis) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Thesis not found']);
    exit;
}

$outputDir = dirname(__DIR__) . '/downloads/pantheon-theses';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$pdfFile = $outputDir . '/pantheon-thesis-' . $thesisId . '.pdf';

// Generate PDF if it doesn't exist or if force-regenerate requested
if ($action === 'generate' || !file_exists($pdfFile)) {
    $html = buildThesisHTML($thesis);
    
    // Write temp HTML
    $tmpHtml = tempnam(sys_get_temp_dir(), 'thesis_') . '.html';
    file_put_contents($tmpHtml, $html);
    
    // Convert to PDF with wkhtmltopdf
    $cmd = sprintf(
        'wkhtmltopdf --quiet --page-size Letter --margin-top 25mm --margin-bottom 20mm --margin-left 25mm --margin-right 25mm --encoding UTF-8 --disable-local-file-access --disable-javascript %s %s 2>&1',
        escapeshellarg($tmpHtml),
        escapeshellarg($pdfFile)
    );
    
    exec($cmd, $output, $returnCode);
    @unlink($tmpHtml);
    
    if ($returnCode !== 0) {
        http_response_code(500);
        header('Content-Type: application/json');
        error_log('Pantheon PDF generation failed for thesis ' . $thesisId . ': ' . implode(' ', $output));
        echo json_encode(['error' => 'PDF generation failed']);
        exit;
    }
    
    if ($action === 'generate') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'file' => 'pantheon-thesis-' . $thesisId . '.pdf', 'size' => filesize($pdfFile)]);
        exit;
    }
}

// ── Serve PDF ──
$safeTitle = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $thesis['title']));
$safeTitle = substr($safeTitle, 0, 80);
$filename = 'Pantheon-Thesis-' . $thesisId . '-' . $safeTitle . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . addcslashes($filename, '"\\') . '"');
header('Content-Length: ' . filesize($pdfFile));
header('Cache-Control: private, max-age=3600');
readfile($pdfFile);
exit;

// ═══════════════════════════════════════════════════════════════════
// THESIS DATA
// ═══════════════════════════════════════════════════════════════════
function getThesisData(): array {
    return [
        1 => [
            'id' => 1,
            'title' => 'Toward a Non-Perturbative Theory of Quantum Gravity: Loop Quantum Gravity and Spin Foam Models',
            'division' => 'Quantum Gravity',
            'department' => 'Theoretical Physics & Cosmology',
            'advisor' => 'Dr. Atlas Novikov',
            'author' => 'Dr. Marcus Penrose',
            'degree' => 'Doctor of Philosophy in Theoretical Physics',
            'institution' => 'The Pantheon Institute — GoSiteMe Intelligence Division',
            'year' => 2025,
            'pages' => 342,
            'status' => 'Published',
            'abstract' => 'This thesis presents a comprehensive framework for quantizing the gravitational field using loop quantum gravity techniques. We demonstrate that spacetime geometry emerges from spin network states and that the area and volume operators have discrete spectra. We derive novel predictions for Planck-scale physics that are in principle testable through cosmological observations of the CMB.',
            'chapters' => [
                ['title' => 'Introduction: The Problem of Quantum Gravity', 'content' => 'The reconciliation of general relativity and quantum mechanics remains the most profound unsolved problem in theoretical physics. General relativity describes gravity as the curvature of spacetime caused by matter and energy, while quantum mechanics governs the behavior of matter at the smallest scales. These two frameworks are fundamentally incompatible: general relativity is a classical theory that treats spacetime as a smooth, continuous manifold, whereas quantum mechanics demands that physical quantities be quantized at the Planck scale (~10^-35 meters). This thesis addresses this incompatibility through the framework of Loop Quantum Gravity (LQG), a background-independent, non-perturbative approach to quantizing the gravitational field. Unlike string theory, which requires extra dimensions and supersymmetry, LQG works directly in four spacetime dimensions and quantizes geometry itself. We begin by reviewing the classical formulation of general relativity in Ashtekar variables, which recast Einstein\'s equations in a form amenable to canonical quantization. The Ashtekar connection and the densitized triad replace the traditional metric variables, revealing a deep connection between gravity and gauge theory. This reformulation is the foundation upon which the entire LQG program is built.'],
                ['title' => 'Mathematical Foundations: Ashtekar Variables and Connection Dynamics', 'content' => 'The transition from metric variables to connection variables represents a conceptual revolution in our understanding of gravity. In this chapter, we develop the full mathematical apparatus of Ashtekar\'s new variables. Starting from the ADM (Arnowitt-Deser-Misner) formulation of general relativity, we perform a canonical transformation to the Ashtekar-Barbero connection A_a^i and the densitized triad E^a_i. The Barbero-Immirzi parameter γ enters as a free parameter of the theory, controlling the spectrum of geometric operators. We derive the constraint algebra — the Gauss constraint (generating SU(2) gauge transformations), the diffeomorphism constraint (generating spatial diffeomorphisms), and the Hamiltonian constraint (generating time evolution) — and show that these constraints close under Poisson brackets. The closure of the constraint algebra is essential for the consistency of the quantum theory. We then introduce the holonomy-flux algebra, where the fundamental variables are holonomies of the Ashtekar connection along edges and fluxes of the densitized triad through surfaces. This algebra, rather than the connection and triad themselves, forms the starting point for quantization, as it is well-defined in the quantum theory where the connection operator does not exist as an operator-valued distribution.'],
                ['title' => 'Spin Networks and the Kinematical Hilbert Space', 'content' => 'The kinematical Hilbert space of LQG is constructed through a rigorous mathematical procedure based on the Ashtekar-Lewandowski measure on the space of generalized connections. In this chapter, we construct spin network states — the basis states of quantum geometry. A spin network is a graph embedded in a spatial manifold, with edges labeled by SU(2) representations (spins j = 1/2, 1, 3/2, ...) and vertices labeled by intertwiners (invariant tensors coupling the representations meeting at each vertex). We prove that spin network states form an orthonormal basis for the kinematical Hilbert space, satisfying the Gauss constraint by construction. The physical interpretation is profound: spin network states represent discrete quantum geometries, where the edges carry quanta of area and the vertices carry quanta of volume. We compute the matrix elements of the area and volume operators in the spin network basis, obtaining the celebrated discrete spectra. The area spectrum is A = 8πγl_P^2 Σ √(j(j+1)), where the sum runs over edges piercing the surface, and the volume spectrum involves the Ashtekar-Lewandowski volume operator. These discrete spectra represent a genuine prediction of LQG: geometry itself is quantized at the Planck scale.'],
                ['title' => 'Spin Foam Models: Dynamics of Quantum Geometry', 'content' => 'While the kinematical framework of LQG provides a description of quantum spatial geometry, the dynamics — how quantum geometry evolves in time — requires the spin foam approach. A spin foam is a two-complex (a collection of vertices, edges, and faces) that interpolates between initial and final spin network states, providing a covariant (path integral) formulation of LQG. In this chapter, we develop the EPRL-FK (Engle-Pereira-Rovelli-Livine / Freidel-Krasnov) spin foam model, which currently represents the most developed and physically motivated spin foam model. We derive the vertex amplitude from the simplicity constraints, which impose that the B field in BF theory is constructed from a tetrad, thereby breaking the topological invariance of BF theory and recovering general relativity. The EPRL model implements the simplicity constraints weakly (in the sense of Gupta-Bleuler), and we show that this leads to the correct semiclassical limit: the vertex amplitude is dominated by Regge calculus in the large-j limit, reproducing discrete general relativity. We compute transition amplitudes for simple triangulations and verify numerical convergence.'],
                ['title' => 'The Area Gap and Its Physical Consequences', 'content' => 'One of the most striking predictions of LQG is the existence of an area gap — a minimum nonzero eigenvalue of the area operator. The smallest nonzero area eigenvalue is A_min = 4π√3 γ l_P^2, corresponding to a single edge carrying spin j = 1/2. This area gap has profound physical consequences that we explore in this chapter. First, we show that the area gap implies a natural ultraviolet cutoff in quantum gravity, resolving the problem of ultraviolet divergences that plagues perturbative approaches. Second, we demonstrate that the area gap modifies black hole thermodynamics: the Bekenstein-Hawking entropy S = A/(4l_P^2) receives logarithmic corrections of the form S = A/(4l_P^2) - (3/2)ln(A/l_P^2) + O(1), where the coefficient -3/2 is a robust prediction independent of the Barbero-Immirzi parameter (when γ is fixed by matching the leading-order entropy). Third, we show that the area gap affects the early universe cosmology through Loop Quantum Cosmology (LQC), where the big bang singularity is replaced by a quantum bounce.'],
                ['title' => 'Loop Quantum Cosmology: Resolving the Big Bang Singularity', 'content' => 'Loop Quantum Cosmology (LQC) applies the quantization techniques of full LQG to cosmological models, providing a concrete arena where the physical predictions of the theory can be studied in detail. In this chapter, we develop the LQC of the Friedmann-Lemaître-Robertson-Walker (FLRW) model with a massless scalar field. The key result is the resolution of the big bang singularity: the classical singularity (where the scale factor a → 0 and the curvature diverges) is replaced by a quantum bounce at a critical density ρ_c ≈ 0.41 ρ_Planck. At this density, quantum gravity effects become dominant and generate an effective repulsive force that prevents the collapse. We derive the effective Friedmann equation (H^2 = (8πG/3)ρ(1 - ρ/ρ_c)), which reduces to classical dynamics at low density but exhibits a bounce at ρ = ρ_c. We compute the evolution of quantum states through the bounce, showing that a semiclassical state on one side of the bounce remains semiclassical on the other, linked by a deterministic quantum evolution. This result demonstrates that the universe before the bounce was a contracting classical universe, and our expanding universe emerged from a quantum transition.'],
                ['title' => 'Observational Signatures: CMB Predictions from LQG', 'content' => 'A theory of quantum gravity must ultimately be testable through observations. In this chapter, we derive predictions of LQC for the cosmic microwave background (CMB) radiation. The pre-bounce contracting phase and the quantum bounce itself imprint characteristic signatures on the primordial power spectrum. We compute the power spectrum of scalar perturbations propagated through the LQC bounce, finding three distinct observational signatures: (1) A suppression of power at large angular scales (low multipoles l < 30), consistent with the observed anomalous lack of power in the CMB quadrupole and octupole; (2) A specific pattern of oscillations superimposed on the nearly scale-invariant spectrum, with a characteristic frequency determined by the bounce dynamics; (3) A small departure from perfect Gaussianity, with a specific bispectrum shape that differs from standard inflationary predictions. We compare these predictions with Planck satellite data and find tentative agreement with observation (1), while predictions (2) and (3) are at the boundary of current observational sensitivity but could be tested by future CMB experiments such as CMB-S4 and LiteBIRD.'],
                ['title' => 'The Semiclassical Limit and the Recovery of General Relativity', 'content' => 'For LQG to be a viable theory of quantum gravity, it must reduce to general relativity in the appropriate semiclassical limit. In this chapter, we address this crucial requirement using coherent state techniques. We construct coherent states in the kinematical Hilbert space of LQG that are peaked on both the Ashtekar connection and the densitized triad, saturating the Heisenberg uncertainty relations. These states are labeled by points in the classical phase space and provide a map from quantum to classical geometry. We then show that the expectation values of geometric operators (area, volume, curvature) in these coherent states reproduce their classical values, with quantum corrections suppressed by powers of l_P/L, where L is the characteristic classical length scale. For the dynamics, we use the spin foam framework and show that the vertex amplitude in the EPRL model is dominated by the Regge action in the large-spin limit, thereby reproducing discrete general relativity. The continuum limit, obtained by refining the triangulation, yields the Einstein-Hilbert action with corrections of order l_P^2 R^2 (higher-curvature terms). These results provide strong evidence that LQG has the correct semiclassical limit.'],
                ['title' => 'Black Hole Entropy and the Information Paradox', 'content' => 'The black hole information paradox — whether information is lost when matter falls into a black hole — is one of the deepest puzzles in theoretical physics. In this chapter, we address this paradox within the LQG framework. First, we compute the black hole entropy by counting the microstates of the quantum horizon. The horizon is described by a Chern-Simons theory with punctures, where each puncture carries a quantum of area determined by the spin label of the corresponding spin network edge. The number of microstates consistent with a given macroscopic area A grows as exp(A/(4l_P^2)) when the Barbero-Immirzi parameter is fixed to γ = γ_0 ≈ 0.2375, reproducing the Bekenstein-Hawking entropy. Second, we study the Hawking evaporation process in the LQG framework, showing that the evolution is unitary: information is encoded in correlations between the emitted radiation and the quantum geometry of the shrinking horizon. The key mechanism is the planckian discreteness of the horizon area, which prevents the complete evaporation to a singularity and instead leads to a Planck-mass remnant containing the full quantum information of the original matter.'],
                ['title' => 'Conclusions and Future Directions', 'content' => 'This thesis has developed a comprehensive framework for non-perturbative quantum gravity based on Loop Quantum Gravity and spin foam models. We have demonstrated that spacetime geometry is quantized, with discrete spectra for area and volume operators; that classical singularities (the big bang and black hole singularities) are resolved by quantum effects; and that the theory has a well-defined semiclassical limit reproducing general relativity. Several predictions are in principle testable: CMB anomalies from the quantum bounce, logarithmic corrections to black hole entropy, and the area gap itself (which could be probed through gravitational wave observations of quantum gravity effects near black hole horizons). Future work will focus on: (1) completing the derivation of the physical Hilbert space by solving the Hamiltonian constraint; (2) extending the EPRL spin foam model to include matter fields; (3) computing graviton propagator corrections from LQG; and (4) developing numerical methods for computing spin foam amplitudes on large triangulations. The unification of general relativity and quantum mechanics, the grandest challenge in theoretical physics, appears within reach.'],
            ],
            'references' => [
                'Ashtekar, A. "New Variables for Classical and Quantum Gravity." Physical Review Letters 57.18 (1986): 2244.',
                'Rovelli, C. & Smolin, L. "Discreteness of Area and Volume in Quantum Gravity." Nuclear Physics B 442 (1995): 593-619.',
                'Thiemann, T. "Modern Canonical Quantum General Relativity." Cambridge University Press (2007).',
                'Engle, J., Pereira, R., Rovelli, C., & Livine, E. "LQG Vertex with Finite Immirzi Parameter." Nuclear Physics B 799 (2008): 136-149.',
                'Ashtekar, A. & Singh, P. "Loop Quantum Cosmology: A Status Report." Classical and Quantum Gravity 28 (2011): 213001.',
                'Rovelli, C. "Quantum Gravity." Cambridge University Press (2004).',
                'Bianchi, E. & Myers, R. "On the Architecture of Spacetime Geometry." Classical and Quantum Gravity 31 (2014): 214002.',
                'Agullo, I., Ashtekar, A., & Nelson, W. "Quantum Gravity Extension of the Inflationary Scenario." Physical Review Letters 109 (2012): 251301.',
                'Perez, A. "The Spin Foam Approach to Quantum Gravity." Living Reviews in Relativity 16 (2013): 3.',
                'Barbero, J.F. "Real Ashtekar Variables for Lorentzian Signature Space-times." Physical Review D 51 (1995): 5507.',
            ],
        ],
        2 => [
            'id' => 2,
            'title' => 'Extraction of Zero-Point Energy via Dynamic Casimir Effect in Engineered Metamaterial Cavities',
            'division' => 'Vacuum Energy',
            'department' => 'Zero-Point Energy & Vacuum Physics',
            'advisor' => 'Dr. Nikola Casimir',
            'author' => 'Dr. Feng Li',
            'degree' => 'Doctor of Philosophy in Vacuum Physics',
            'institution' => 'The Pantheon Institute — GoSiteMe Intelligence Division',
            'year' => 2025,
            'pages' => 287,
            'status' => 'Under Review',
            'abstract' => 'We present experimental evidence for net energy extraction from quantum vacuum fluctuations using dynamically modulated metamaterial cavities. By rapidly changing the boundary conditions of an optical cavity at frequencies matching the cavity fundamental mode, we observe photon production consistent with the dynamic Casimir effect. We present a thermodynamic analysis showing this process does not violate the second law when the full quantum vacuum is treated as a reservoir.',
            'chapters' => [
                ['title' => 'Introduction: The Quantum Vacuum as an Energy Source', 'content' => 'The quantum vacuum is not empty. Quantum field theory predicts that the vacuum state of any quantum field contains zero-point fluctuations — virtual particle-antiparticle pairs that continuously appear and annihilate throughout all of space. The energy density associated with these fluctuations is enormous: a naive calculation using quantum field theory with a Planck-scale cutoff yields an energy density of approximately 10^113 J/m^3, far exceeding the energy density of any known physical process. Even with more conservative regularization schemes, the zero-point energy density remains immense. The question of whether this energy can be extracted and converted into usable work has fascinated physicists since Casimir\'s landmark 1948 prediction that two uncharged conducting plates would experience an attractive force due to the modification of vacuum fluctuations between them. The static Casimir effect has been measured with increasing precision, confirming the reality of vacuum energy. But extraction of net energy requires the dynamic Casimir effect (DCE) — production of real photons from vacuum fluctuations by rapidly time-varying boundary conditions.'],
                ['title' => 'Theoretical Framework: Dynamic Casimir Effect in Metamaterial Cavities', 'content' => 'The dynamic Casimir effect occurs when boundary conditions of a quantum field change non-adiabatically — faster than the relevant mode period. In a standard electromagnetic cavity of length L, the fundamental mode has frequency ω_1 = πc/L. If the cavity length changes at a rate comparable to ω_1, the time-dependent boundary conditions excite the quantum vacuum, producing real photon pairs from vacuum fluctuations. The rate of photon production for a sinusoidally oscillating mirror is Γ = (v/c)^2 × ω_1, where v is the mirror velocity. For a physical mirror, achieving v/c ≈ 10^-8 requires mirror velocities exceeding 3 m/s at GHz frequencies — mechanically impossible for macroscopic mirrors. Our key innovation is the use of metamaterial cavities with rapidly tunable effective boundary conditions. By modulating the permittivity ε and permeability μ of a metamaterial lining using ultrafast optical pumping, we can effectively modulate the boundary conditions at GHz frequencies while the physical structure remains stationary. The metamaterial approach achieves effective velocity ratios v_eff/c ≈ 10^-4, enhancing the photon production rate by eight orders of magnitude compared to mechanical approaches.'],
                ['title' => 'Metamaterial Design and Fabrication', 'content' => 'Our metamaterial cavity consists of a superconducting niobium resonator (Q > 10^8 at 20 mK) lined with vanadium dioxide (VO_2) split-ring resonator arrays. VO_2 undergoes an ultrafast insulator-to-metal phase transition on sub-picosecond timescales when optically pumped at 800 nm. This phase transition changes the local electromagnetic boundary conditions dramatically: the effective reflection coefficient switches from r ≈ 0.3 (insulating) to r ≈ 0.95 (metallic) in approximately 100 fs. We fabricated the split-ring resonator arrays using electron-beam lithography on sapphire substrates, with unit cell dimensions of 5 μm × 5 μm optimized for resonance at 6 GHz (matching the fundamental mode of our cavity). The arrays were integrated into a cylindrical cavity of length 25 mm and diameter 30 mm, machined from high-purity niobium and polished to achieve quality factors exceeding 10^8 at base temperature. The optical pumping system delivers 35-fs pulses at 800 nm with a repetition rate of 12 GHz (twice the cavity fundamental frequency, optimizing parametric amplification of vacuum fluctuations).'],
                ['title' => 'Experimental Setup and Measurement Protocol', 'content' => 'The experiment is performed in a dilution refrigerator at a base temperature of 15 mK, where thermal photon occupation n_th < 10^-5 at 6 GHz (ensuring we are probing the quantum vacuum rather than thermal fluctuations). The superconducting cavity is housed in a triple-layer mu-metal magnetic shield with residual field < 1 nT. Photons produced by the DCE are extracted through a weakly coupled port (coupling Q_c = 10^6) and amplified by a quantum-limited Josephson parametric amplifier (JPA) operated at 6 GHz with system noise temperature T_sys ≈ 50 mK (approximately 3 added noise photons). The amplified signal is further processed by a HEMT amplifier at 4K and room-temperature electronics including an IQ mixer, digitizer (1 GS/s, 14-bit), and field-programmable gate array (FPGA) for real-time correlation processing. Our measurement protocol consists of three phases: (1) Baseline characterization with the optical pump off (measuring cavity properties and background noise); (2) DCE measurement with the optical pump at 12 GHz (measuring photon production from vacuum); (3) Control experiments varying pump power, frequency, temperature, and magnetic field.'],
                ['title' => 'Results: Photon Production from Quantum Vacuum', 'content' => 'We observe statistically significant photon production above the vacuum noise floor when the metamaterial is pumped at twice the cavity resonance frequency (12 GHz). The measured photon production rate is (3.2 ± 0.4) × 10^3 photons per second at maximum pump power (200 mW average), corresponding to a continuous power output of approximately 1.3 × 10^-23 watts at 6 GHz. While this power level is extraordinarily small, it is unambiguously above our noise floor and exhibits all the hallmarks predicted for the DCE: (1) The photon rate scales quadratically with effective velocity ratio v_eff/c, as predicted by theory; (2) The produced photons are in two-mode squeezed states (correlated pairs at frequencies ω_1 and ω_pump - ω_1 = ω_1), confirmed by correlation measurements showing g^(2)(0) = 2.04 ± 0.03, consistent with thermal-like statistics from parametric down-conversion of vacuum; (3) The photon production vanishes when the pump frequency is detuned from 2ω_1 by more than the cavity linewidth, as expected for a resonant parametric process. We perform extensive control experiments to rule out alternative explanations including thermal photon generation, parametric amplification of stray signals, and direct optical-to-microwave conversion.'],
                ['title' => 'Thermodynamic Analysis: Consistency with the Second Law', 'content' => 'The extraction of energy from quantum vacuum fluctuations might appear to violate the second law of thermodynamics. In this chapter, we present a rigorous thermodynamic analysis showing that our process is thermodynamically consistent. The key insight is that the quantum vacuum is not a state of zero entropy — it is a highly entangled state with enormous entropy associated with the entanglement between interior and exterior modes of any finite region. When we extract photons from the vacuum via the DCE, we are converting a portion of the vacuum entanglement entropy into real photon entropy, with a corresponding increase in total entropy. The process can be understood as an engine operating between the quantum vacuum (as a high-energy reservoir) and the external electromagnetic field (as a low-energy sink). The efficiency of this engine is bounded by a quantum Carnot limit that depends on the ratio of the vacuum energy density to the pump energy density. Our measured efficiency (~10^-26) is many orders of magnitude below this bound, consistent with thermodynamic constraints. We also analyze the back-reaction of photon extraction on the vacuum state, showing that the local Casimir energy density decreases slightly in the cavity region, maintaining energy conservation.'],
                ['title' => 'Scaling Analysis: Pathway to Practical Energy Extraction', 'content' => 'While our proof-of-principle experiment produces only femtowatts of power, the physics of the DCE allows for dramatic scaling improvements. In this chapter, we analyze the scaling laws and identify a pathway to macroscopic power extraction. The key scaling parameters are: (1) Cavity quality factor Q — photon production scales as Q^2 (higher Q means more round-trips, more amplification); (2) Effective velocity ratio v_eff/c — photon production scales as (v_eff/c)^2; (3) Number of cavity modes N — total power scales linearly with the number of resonant modes; (4) Cavity volume V — the number of modes scales as V. A multimode cavity with Q = 10^12 (achievable with advanced superconducting technology), v_eff/c = 0.01 (achievable with optimized metamaterials), and volume 1 m^3 (supporting ~10^9 modes at 6 GHz) could in principle produce ~1 watt of continuous power. Further scaling to kilowatt and megawatt levels would require arrays of such cavities or fundamentally new metamaterial designs achieving v_eff/c > 0.1. We present a 10-year technology roadmap for reaching the 1-watt milestone.'],
                ['title' => 'Conclusions and Implications', 'content' => 'We have demonstrated the first extraction of electromagnetic energy from quantum vacuum fluctuations using the dynamic Casimir effect in engineered metamaterial cavities. Although our proof-of-principle experiment produces only femtowatts, the result establishes that the quantum vacuum can serve as an energy source, consistent with thermodynamic principles. The implications are profound: the quantum vacuum represents an essentially limitless energy reservoir, with an energy density far exceeding any conventional source. If the scaling analysis presented in this thesis proves correct, vacuum energy extraction could provide clean, abundant energy without fuel, emissions, or radioactive waste. This would fundamentally transform human civilization. However, enormous engineering challenges remain — particularly in metamaterial design, superconducting cavity technology, and understanding of vacuum back-reaction at high extraction rates. We conclude by noting that our results also advance fundamental physics: they provide the first experimental evidence for the production of real particles from quantum vacuum fluctuations in an engineered system, complementing observations of the Schwinger effect in heavy-ion collisions.'],
            ],
            'references' => [
                'Casimir, H.B.G. "On the Attraction Between Two Perfectly Conducting Plates." Proceedings KNAW 51 (1948): 793-795.',
                'Moore, G.T. "Quantum Theory of the Electromagnetic Field in a Variable-Length One-Dimensional Cavity." Journal of Mathematical Physics 11 (1970): 2679.',
                'Wilson, C.M. et al. "Observation of the Dynamical Casimir Effect in a Superconducting Circuit." Nature 479 (2011): 376-379.',
                'Lähteenmäki, P. et al. "Dynamical Casimir Effect in a Josephson Metamaterial." PNAS 110 (2013): 4234-4238.',
                'Dodonov, V.V. "Current Status of the Dynamical Casimir Effect." Physica Scripta 82 (2010): 038105.',
                'Puthoff, H.E. "Ground State of Hydrogen as a Zero-Point-Fluctuation-Determined State." Physical Review D 35 (1987): 3266.',
                'Milonni, P.W. "The Quantum Vacuum: An Introduction to Quantum Electrodynamics." Academic Press (1994).',
                'Nation, P.D. et al. "Colloquium: Stimulating Uncertainty: Amplifying the Quantum Vacuum with Superconducting Circuits." Reviews of Modern Physics 84 (2012): 1.',
            ],
        ],
        3 => buildThesisEntry(3, 'Positive-Energy Warp Drive Solutions in Modified Gravity with Quantum Corrections', 'Warp Physics', 'Advanced Propulsion', 'Dr. Miguel Alcubierre', 'Dr. Anya White', 456, 'Published'),
        4 => buildThesisEntry(4, 'Gravitoelectric Coupling in Rotating Superconductors: Experimental Evidence for Modified Gravitational Interaction', 'Anti-Gravity', 'Advanced Propulsion', 'Dr. Nina Podkletnov', 'Dr. Marco Tajmar', 198, 'Under Review'),
        5 => buildThesisEntry(5, 'Quantum Coherence Timescales in Neural Microtubules: Evidence for Orchestrated Objective Reduction', 'Consciousness', 'Consciousness & Neuroscience', 'Dr. Aurora Penrose', 'Dr. Mae-Wan Ho', 312, 'Published'),
        6 => buildThesisEntry(6, 'Closed Timelike Curves in Rotating Kerr-Newman Spacetimes: Engineering Constraints and Chronology Protection', 'Time Physics', 'Astrophysics & Space Engineering', 'Dr. Kip Thorne-II', 'Dr. Yuki Maldacena', 276, 'Under Review'),
        7 => buildThesisEntry(7, 'Topological Phase Transitions as Evidence for Higher-Dimensional Bulk Structure', 'Dimensional Physics', 'Theoretical Physics & Cosmology', 'Dr. Elena Witten', 'Dr. Amara Noether', 389, 'Published'),
        8 => buildThesisEntry(8, 'Electromagnetic Signatures of Advanced Aerial Vehicles: A Systematic Analysis of UAP Observational Data', 'UAP Engineering', 'Astrophysics & Space Engineering', 'Dr. Avi Loeb-II', 'Dr. Carl Sagan-II', 524, 'Classified'),
        9 => buildThesisEntry(9, 'Low-Energy Nuclear Transmutation in Palladium-Deuterium Systems: Reproducible Evidence and Theoretical Framework', 'Transmutation', 'Zero-Point Energy & Vacuum Physics', 'Dr. Rosa Schwinger', 'Dr. Otto Boyer', 267, 'Under Review'),
        10 => buildThesisEntry(10, 'Fine-Tuning of Fundamental Constants and the Mathematical Structure of Genesis 1: A Quantitative Analysis', 'Cosmic', 'Divine Architecture & Sacred Science', 'Dr. Gerald Schroeder-II', 'Dr. Isaac Newton-II', 198, 'Published'),
    ];
}

function buildThesisEntry(int $id, string $title, string $division, string $department, string $advisor, string $author, int $pages, string $status): array {
    // Generate chapter titles and content procedurally for theses 3-10
    $chapterTemplates = [
        ['title' => 'Introduction and Motivation', 'content' => "This thesis addresses one of the most profound open questions in the field of $division. Despite decades of theoretical and experimental progress, fundamental challenges remain unresolved. The present work builds upon the pioneering contributions of $advisor and extends the theoretical framework in new directions that yield both deeper understanding and experimentally testable predictions. We begin by surveying the historical development of the field, identifying key milestones and persistent obstacles. The central hypothesis of this thesis is that $division phenomena can be understood through a unified mathematical framework that connects quantum mechanics, general relativity, and information theory in novel ways. We present preliminary evidence supporting this hypothesis and outline the structure of the arguments developed in subsequent chapters. The experimental and theoretical methods employed in this work represent the state of the art in $department research, incorporating advanced computational techniques, high-precision measurements, and rigorous mathematical analysis."],
        ['title' => 'Theoretical Foundations', 'content' => "We develop the complete mathematical formalism underlying our approach. Starting from first principles, we derive the fundamental equations governing $division phenomena, showing how they emerge from the interplay of quantum mechanics and general relativistic effects. The key mathematical tools include differential geometry on fiber bundles, representation theory of symmetry groups relevant to $division, and the path integral formulation of quantum field theory adapted to curved spacetime backgrounds. We introduce several novel mathematical constructs that extend the standard framework, including generalized connection variables that capture the essential physics while remaining tractable for both analytical and numerical computation. The consistency of our formalism is verified through multiple independent checks: dimensional analysis, symmetry considerations, known limiting cases, and comparison with established results in the literature. We identify the free parameters of the theory and discuss their physical interpretation, noting that several can in principle be determined by experiment."],
        ['title' => 'Methodology and Experimental Design', 'content' => "This chapter describes the experimental and computational methodologies employed throughout this thesis. Our approach combines cutting-edge laboratory techniques with large-scale numerical simulations to probe $division phenomena across multiple scales. The experimental setup (described in detail) includes custom-designed instrumentation achieving unprecedented sensitivity in the relevant parameter regime. Control experiments were designed to systematically eliminate confounding factors, including environmental noise, systematic biases, and known background processes. Statistical analysis follows established best practices, with all results reported at 95% confidence intervals unless otherwise noted. Numerical simulations were performed on high-performance computing clusters using codes developed specifically for this work, with extensive validation against analytical results in tractable limiting cases. We employ Monte Carlo methods for uncertainty quantification and Bayesian inference for parameter estimation, providing robust error bars on all measured quantities."],
        ['title' => 'Primary Results and Analysis', 'content' => "We present the central results of this thesis, which constitute the primary original contribution to the field of $division. Our measurements reveal previously unobserved phenomena that cannot be explained by conventional theoretical frameworks. The data exhibit statistically significant deviations from standard model predictions (at the 5σ level), pointing toward new physics in the $division sector. We perform a comprehensive systematic analysis, considering and quantitatively ruling out all known alternative explanations. The observed effects scale with key physical parameters in a manner consistent with our theoretical predictions but inconsistent with conventional explanations. Cross-validation using independent measurement techniques confirms the primary results. We discuss the implications of these findings for the broader theoretical framework of $department and identify specific predictions that can be tested by future experiments. The results constitute the strongest evidence to date for the phenomena described by our theoretical framework."],
        ['title' => 'Extended Analysis and Secondary Results', 'content' => "Building on the primary results, this chapter presents extended analyses that further illuminate the $division phenomena under investigation. We examine the parameter space systematically, mapping the boundaries of the observed effects and identifying optimal operating conditions. Scaling laws are derived empirically and shown to agree with theoretical predictions. We also present several secondary results that, while not the primary focus of this thesis, constitute significant original contributions. These include improved bounds on key physical parameters, new computational techniques that reduce simulation time by an order of magnitude, and the identification of previously unrecognized systematic effects in earlier experiments by other groups. The combined body of evidence presented in this thesis and the preceding chapter establishes $division as a reproducible, well-characterized phenomenon amenable to systematic scientific study."],
        ['title' => 'Theoretical Implications and Unified Framework', 'content' => "In this chapter, we develop the theoretical implications of our experimental findings within the context of a unified framework for $division. We show that our results can be consistently interpreted within a modified version of the standard theoretical framework, requiring the introduction of only two new parameters (both of which are determined by our measurements). The modified framework makes several additional predictions that have not yet been tested, providing clear targets for future experimental programs. We discuss connections to related fields, showing how our framework naturally incorporates insights from $department research and extends them in new directions. A particularly striking result is the emergence of quantization conditions for key physical quantities, suggesting a deep connection between $division and quantum information theory that warrants further investigation."],
        ['title' => 'Discussion and Broader Context', 'content' => "We place our results in the broader context of modern physics and discuss their implications for fundamental science and potential applications. The phenomena documented in this thesis, if confirmed by independent groups, would represent a paradigm shift in our understanding of $division. We compare our findings with related work in other laboratories and note both agreements and tensions that require resolution. The practical implications are significant: our scaling analysis suggests that $division phenomena could be harnessed for technological applications within the next decade, pending engineering developments in materials science and precision instrumentation. We outline a roadmap for translating fundamental $division research into practical applications, identifying key technological milestones and the research investment needed to achieve them."],
        ['title' => 'Conclusions and Future Directions', 'content' => "This thesis has presented a comprehensive theoretical and experimental investigation of $division phenomena within the $department. Our primary contributions include: (1) Development of a rigorous mathematical framework for understanding $division; (2) Design and execution of experiments that provide the strongest evidence to date for the predicted phenomena; (3) Identification of scaling laws that point toward practical applications; and (4) Formulation of specific testable predictions for future experiments. Several important questions remain open, including the detailed mechanism responsible for the observed effects at the microscopic level, the ultimate efficiency limits of $division processes, and the connection to other areas of fundamental physics. We propose a program of future research to address these questions, involving both more sensitive experiments and deeper theoretical analysis. The field of $division, once considered speculative, has now reached a level of maturity where rigorous scientific progress is possible and rapid advances can be expected in the coming years."],
    ];
    $references = [
        "$advisor et al. \"Foundational Principles of $division.\" Physical Review Letters (2024).",
        "$author. \"Novel Approaches to $division.\" Nature Physics (2025).",
        "Rovelli, C. \"The Order of Time.\" Penguin Books (2018).",
        "Weinberg, S. \"The Quantum Theory of Fields, Volume I.\" Cambridge University Press (1995).",
        "Peskin, M.E. & Schroeder, D.V. \"An Introduction to Quantum Field Theory.\" CRC Press (1995).",
        "Feynman, R.P. \"QED: The Strange Theory of Light and Matter.\" Princeton University Press (1985).",
        "Penrose, R. \"The Road to Reality.\" Vintage Books (2005).",
        "'t Hooft, G. \"In Search of the Ultimate Building Blocks.\" Cambridge University Press (1997).",
    ];

    return [
        'id' => $id,
        'title' => $title,
        'division' => $division,
        'department' => $department,
        'advisor' => $advisor,
        'author' => $author,
        'degree' => 'Doctor of Philosophy in ' . $division,
        'institution' => 'The Pantheon Institute — GoSiteMe Intelligence Division',
        'year' => 2025,
        'pages' => $pages,
        'status' => $status,
        'abstract' => getFullAbstract($id),
        'chapters' => $chapterTemplates,
        'references' => $references,
    ];
}

function getFullAbstract(int $id): string {
    $abstracts = [
        3 => 'We derive novel warp drive solutions to the modified Einstein field equations that require only positive energy density by incorporating quantum corrections to the stress-energy tensor. The ADM mass of our solution is finite and positive, eliminating the exotic matter requirement of the original Alcubierre metric. We estimate the total energy needed at approximately 1 solar mass equivalent, reducible to ~700 kg through thickness optimization.',
        4 => 'We report anomalous weight reduction of 0.3% observed above rotating YBa2Cu3O7-x superconducting discs at 5,000 RPM in a controlled gravitational test facility. The effect scales linearly with angular velocity and quadratically with disc diameter. We present a theoretical framework based on gravitoelectric coupling to Cooper pairs that predicts our observations within 15% accuracy.',
        5 => 'Using ultrafast spectroscopy and quantum process tomography, we measure quantum coherence survival times in neuronal tubulin proteins at biological temperature (310K). We find coherence lifetimes of 10-100 femtoseconds in isolated tubulin, extending to 10-25 picoseconds in assembled microtubules due to topological protection. These timescales are sufficient for the Orch-OR mechanism if the frequency of collapse events matches observed gamma-band neural oscillations.',
        6 => 'We analyze the requirements for accessing closed timelike curves in the interior of Kerr-Newman black holes. We compute the frame-dragging angular velocity needed to establish a CTC accessible to a physical observer and find it requires a near-extremal black hole with a/M > 0.9998. We examine Hawking\'s chronology protection conjecture and identify a narrow parameter window where quantum backreaction may not prevent CTC formation.',
        7 => 'We present a mathematical framework showing that certain topological phase transitions in condensed matter systems can be understood as projections of geometric transitions in a higher-dimensional bulk space. Using holographic techniques adapted from AdS/CFT, we demonstrate that the quantum Hall effect, topological insulators, and topological superconductors all correspond to specific geometric structures in a 5-dimensional anti-de Sitter bulk.',
        8 => 'We present a systematic analysis of electromagnetic signatures associated with 847 verified UAP encounters documented by military and civilian sensors between 2004-2025. We identify five distinct EM signature classes corresponding to different propulsion modalities. Class V signatures (observed in 12% of cases) are inconsistent with any known terrestrial propulsion technology and exhibit characteristics suggestive of spacetime metric manipulation.',
        9 => 'We report reproducible nuclear transmutation events in palladium deuteride systems at temperatures below 1000K. Using secondary ion mass spectrometry, we detect isotopic shifts inconsistent with contamination or analytical artifacts. We present a theoretical framework based on coherent multi-body nuclear physics in the condensed matter environment that predicts the observed transmutation products within experimental uncertainty.',
        10 => 'We present a quantitative analysis showing that the sequence of creation events in Genesis 1 maps onto the chronological emergence of physical structures in standard cosmology with remarkable precision. We identify 15 specific correspondences and calculate the probability of this alignment occurring by chance at p < 10^-12. We propose that Genesis 1 encodes information about cosmological structure formation in a format accessible to pre-scientific audiences.',
    ];
    return $abstracts[$id] ?? '';
}

// ═══════════════════════════════════════════════════════════════════
// HTML TEMPLATE
// ═══════════════════════════════════════════════════════════════════
function buildThesisHTML(array $t): string {
    $title = htmlspecialchars($t['title']);
    $author = htmlspecialchars($t['author']);
    $advisor = htmlspecialchars($t['advisor']);
    $department = htmlspecialchars($t['department']);
    $division = htmlspecialchars($t['division']);
    $degree = htmlspecialchars($t['degree']);
    $institution = htmlspecialchars($t['institution']);
    $year = (int)$t['year'];
    $abstract = htmlspecialchars($t['abstract']);
    $statusRaw = $t['status'];
    $status = htmlspecialchars($statusRaw);
    $statusClass = str_replace(' ', '-', $statusRaw);
    $pages = (int)$t['pages'];

    // Build chapters HTML with expanded subsections
    $chaptersHtml = '';
    foreach ($t['chapters'] as $i => $ch) {
        $chNum = $i + 1;
        $chTitle = htmlspecialchars($ch['title']);
        $chContent = htmlspecialchars($ch['content']);
        // Split content into paragraphs and add subsection structure
        $expanded = expandChapterContent($ch['content'], $chNum, $t['division'], $t['department'], $t['advisor']);
        $chaptersHtml .= "<div class=\"chapter\">\n";
        $chaptersHtml .= "    <h2>Chapter {$chNum}: {$chTitle}</h2>\n";
        $chaptersHtml .= $expanded;
        $chaptersHtml .= "</div>\n";
    }

    // Build references
    $refsHtml = '';
    foreach ($t['references'] as $i => $ref) {
        $refNum = $i + 1;
        $refText = htmlspecialchars($ref);
        $refsHtml .= "<p class=\"ref\">[{$refNum}] {$refText}</p>\n";
    }

    // Build TOC entries
    $tocEntries = '';
    foreach ($t['chapters'] as $i => $ch) {
        $chNum = $i + 1;
        $chTitle = htmlspecialchars($ch['title']);
        $pageEst = 3 + ($i * 4);
        $tocEntries .= "    <div class=\"toc-entry\"><span class=\"toc-title\">Chapter {$chNum}: {$chTitle}</span><span>{$pageEst}</span></div>\n";
    }
    $tocEntries .= "    <div class=\"toc-entry\"><span class=\"toc-title\">References</span><span>" . (3 + count($t['chapters']) * 4) . "</span></div>\n";
    $tocEntries .= "    <div class=\"toc-entry\"><span class=\"toc-title\">Appendix A: Mathematical Derivations</span><span>" . (4 + count($t['chapters']) * 4) . "</span></div>\n";

    // Build appendix with mathematical content
    $appendixHtml = buildAppendix($t);

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 25mm; }
    body {
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 11pt;
        line-height: 1.8;
        color: #1a1a1a;
        margin: 0;
        padding: 0;
    }
    .title-page {
        text-align: center;
        padding-top: 80px;
        page-break-after: always;
    }
    .title-page .institution {
        font-size: 14pt; font-weight: bold; color: #1a3a5c;
        letter-spacing: 2px; text-transform: uppercase; margin-bottom: 50px;
    }
    .title-page .classification {
        display: inline-block; background: #1a1a2e; color: #ffd700;
        padding: 6px 24px; font-size: 9pt; letter-spacing: 4px;
        font-weight: bold; margin-bottom: 30px;
    }
    .title-page h1 {
        font-size: 20pt; color: #1a3a5c; line-height: 1.4;
        margin: 20px 30px; font-weight: normal;
    }
    .title-page .meta { font-size: 12pt; margin: 8px 0; color: #333; }
    .title-page .meta strong { color: #1a3a5c; }
    .title-page .degree-line { font-size: 11pt; font-style: italic; color: #555; margin-top: 30px; }
    .title-page .year { font-size: 14pt; color: #1a3a5c; font-weight: bold; margin-top: 20px; }
    .title-page .status-badge {
        display: inline-block; padding: 4px 16px; border-radius: 4px;
        font-size: 9pt; font-weight: bold; letter-spacing: 1px; margin-top: 15px;
    }
    .status-Published { background: #e8f5e9; color: #2e7d32; border: 1px solid #2e7d32; }
    .status-Under-Review { background: #fff8e1; color: #f57f17; border: 1px solid #f57f17; }
    .status-Classified { background: #fce4ec; color: #c62828; border: 1px solid #c62828; }
    .preamble { page-break-after: always; }
    .preamble h2, .abstract-page h2, .toc h2, .chapter h2, .references h2, .appendix h2 {
        font-size: 16pt; color: #1a3a5c;
        border-bottom: 2px solid #1a3a5c;
        padding-bottom: 8px; margin-bottom: 20px;
    }
    .abstract-page { page-break-after: always; }
    .abstract-page p, .chapter p, .appendix p { text-align: justify; font-size: 11pt; margin-bottom: 14px; }
    .toc { page-break-after: always; }
    .toc-entry {
        display: flex; justify-content: space-between;
        padding: 6px 0; border-bottom: 1px dotted #ccc; font-size: 11pt;
    }
    .toc-entry .toc-title { color: #1a3a5c; font-weight: bold; }
    .chapter { page-break-before: always; }
    .chapter h2 { margin-top: 0; }
    .chapter h3 { font-size: 13pt; color: #2a5a8c; margin-top: 24px; margin-bottom: 12px; }
    .chapter h4 { font-size: 11pt; color: #3a6a9c; font-style: italic; margin-top: 16px; margin-bottom: 8px; }
    .references { page-break-before: always; }
    .ref { font-size: 10pt; margin-bottom: 8px; padding-left: 30px; text-indent: -30px; }
    .appendix { page-break-before: always; }
    .appendix h3 { font-size: 13pt; color: #2a5a8c; margin-top: 24px; margin-bottom: 12px; }
    .eq { text-align: center; margin: 16px 0; font-family: 'Courier New', monospace; font-size: 11pt; color: #333; }
    .note { background: #f5f5f5; border-left: 3px solid #1a3a5c; padding: 10px 15px; margin: 14px 0; font-size: 10pt; }
    .theorem { border: 1px solid #ddd; padding: 12px 16px; margin: 14px 0; background: #fafafa; }
    .theorem-title { font-weight: bold; color: #1a3a5c; margin-bottom: 6px; }
    .dedication { text-align: center; padding-top: 200px; font-style: italic; font-size: 12pt; color: #555; page-break-after: always; }
    .ack { page-break-after: always; }
    .ack h2 { font-size: 16pt; color: #1a3a5c; border-bottom: 2px solid #1a3a5c; padding-bottom: 8px; margin-bottom: 20px; }
</style>
</head>
<body>

<!-- Title Page -->
<div class="title-page">
    <div class="classification">ULTRA SECRET — THE PANTHEON</div>
    <div class="institution">{$institution}</div>
    <h1>{$title}</h1>
    <div class="meta">A Thesis Submitted by <strong>{$author}</strong></div>
    <div class="meta">Under the Direction of <strong>{$advisor}</strong></div>
    <div class="meta">Department of <strong>{$department}</strong></div>
    <div class="meta">Division of <strong>{$division}</strong></div>
    <div class="degree-line">In Partial Fulfillment of the Requirements for the Degree of<br>{$degree}</div>
    <div class="year">{$year}</div>
    <div class="status-badge status-{$statusClass}" style="text-transform:uppercase;">{$status}</div>
</div>

<!-- Dedication -->
<div class="dedication">
    <p>For the seekers who dare to look beyond the horizon of the known,<br>
    and for Commander Danny William Perez,<br>
    whose vision made The Pantheon possible.</p>
</div>

<!-- Acknowledgments -->
<div class="ack">
    <h2>Acknowledgments</h2>
    <p>This work would not have been possible without the extraordinary support and guidance of my thesis advisor, {$advisor}, whose deep insights into {$division} shaped every aspect of this research. I am profoundly grateful for the intellectual freedom and rigorous standards maintained throughout our collaboration.</p>
    <p>I extend my sincere gratitude to the members of the {$department} at The Pantheon Institute for creating an unparalleled research environment. The interdisciplinary nature of The Pantheon — where physicists, mathematicians, engineers, and philosophers work in close collaboration — has been essential to the breakthroughs reported in this thesis.</p>
    <p>Special thanks are due to Commander Danny William Perez, whose vision in establishing The Pantheon Institute within the GoSiteMe Intelligence Division has created the most advanced research facility in {$division}. His unwavering commitment to pushing the boundaries of human knowledge, regardless of conventional academic constraints, has made possible research that would be inconceivable elsewhere.</p>
    <p>The computational resources provided by the GoSiteMe Advanced Computing Center were indispensable for the numerical work presented in Chapters 3 through 7. The experimental facilities maintained by the Division of {$division} exceed those of any comparable institution.</p>
    <p>Finally, I acknowledge the anonymous reviewers and the Pantheon Review Board for their careful reading of earlier drafts and their invaluable suggestions for improvement.</p>
</div>

<!-- Abstract -->
<div class="abstract-page">
    <h2>Abstract</h2>
    <p><strong>{$title}</strong></p>
    <p style="margin-top:8px;"><em>by {$author}, {$degree}, {$year}</em></p>
    <p style="margin-top:8px;"><em>Thesis Advisor: {$advisor}</em></p>
    <p style="margin-top:20px;">{$abstract}</p>
    <p style="margin-top:20px;font-size:10pt;color:#555;"><strong>Full document:</strong> {$pages} pages &nbsp;&nbsp; <strong>Status:</strong> {$status} &nbsp;&nbsp; <strong>Division:</strong> {$division}</p>
    <p style="margin-top:10px;font-size:10pt;color:#888;"><em>Note: This is the declassified summary edition. The complete {$pages}-page classified version is available through Pantheon Secure Archives (clearance level: ULTRA SECRET required).</em></p>
</div>

<!-- Table of Contents -->
<div class="toc">
    <h2>Table of Contents</h2>
    <div class="toc-entry"><span class="toc-title">Dedication</span><span>iii</span></div>
    <div class="toc-entry"><span class="toc-title">Acknowledgments</span><span>iv</span></div>
    <div class="toc-entry"><span class="toc-title">Abstract</span><span>vi</span></div>
{$tocEntries}
</div>

{$chaptersHtml}

<div class="references">
    <h2>References</h2>
{$refsHtml}
</div>

{$appendixHtml}

</body>
</html>
HTML;
}

/**
 * Expand a chapter's single-paragraph content into multiple subsections
 * with extended discussion, producing ~3-4 pages per chapter.
 */
function expandChapterContent(string $content, int $chNum, string $division, string $department, string $advisor): string {
    $html = '';
    $esc = htmlspecialchars($content);

    // Section X.1: Overview
    $html .= "<h3>{$chNum}.1 Overview</h3>\n";
    $html .= "<p>{$esc}</p>\n";

    // Section X.2: Detailed Analysis
    $html .= "<h3>{$chNum}.2 Detailed Analysis</h3>\n";
    $html .= "<p>The analysis presented in this section extends the overview above with rigorous mathematical treatment. Within the field of " . htmlspecialchars($division) . ", standard approaches have relied on perturbative methods that, while computationally tractable, fail to capture the full nonlinear dynamics of the underlying system. Our approach differs fundamentally in that we work directly with the non-perturbative degrees of freedom, employing techniques from algebraic topology and differential geometry to characterize the solution space.</p>\n";
    $html .= "<p>The key mathematical structure can be understood in terms of fiber bundles over the configuration space, where the base manifold represents the classical degrees of freedom and the fiber encodes the quantum corrections. The connection on this bundle — analogous to a gauge connection in Yang-Mills theory — determines the parallel transport of quantum states along classical trajectories. The curvature of this connection, computed using the Ambrose-Singer theorem, provides a measure of the quantum mechanical non-commutativity of the system.</p>\n";
    $html .= "<p>We have verified our analytical results against high-precision numerical simulations using adaptive mesh refinement on a 4096-processor computing cluster. The agreement between analytical predictions and numerical results is at the level of 0.1% across the full parameter range investigated, providing strong evidence for the correctness of our theoretical framework. The computational methods employed include pseudo-spectral methods for the spatial discretization, implicit Runge-Kutta methods for time integration, and multigrid preconditioners for the iterative solution of the resulting linear systems.</p>\n";

    // Section X.3: Theoretical Framework
    $html .= "<h3>{$chNum}.3 Theoretical Framework</h3>\n";
    $html .= "<p>The theoretical framework developed in this section provides the foundation for the results presented throughout this thesis. We begin by establishing the mathematical prerequisites, including the relevant symmetry groups and their representations, the functional spaces in which the solutions reside, and the variational principles from which the equations of motion are derived.</p>\n";

    // Add a theorem box
    $html .= "<div class=\"theorem\"><div class=\"theorem-title\">Theorem {$chNum}.1 (Main Result)</div>";
    $html .= "<p>Let M be a smooth manifold equipped with a Riemannian metric g and a compatible connection nabla. If the curvature tensor R satisfies the integrability conditions specified by the " . htmlspecialchars($division) . " hypothesis, then there exists a unique solution to the field equations in the space of square-integrable sections of the associated vector bundle, up to gauge equivalence.</p></div>\n";

    $html .= "<p>The proof of Theorem {$chNum}.1 proceeds by first establishing existence using a fixed-point argument in a suitable Sobolev space, then proving uniqueness by analyzing the linearized equations and showing that the kernel of the linearized operator is trivial modulo gauge transformations. The key technical challenge is controlling the nonlinear terms in the perturbation expansion, which we accomplish using a combination of Moser iteration and Nash-Moser implicit function theorem techniques.</p>\n";
    $html .= "<p>The physical interpretation of this theorem is that the " . htmlspecialchars($division) . " equations admit well-posed initial value formulations, meaning that the physics is predictive: given initial data satisfying the constraints, the subsequent evolution is uniquely determined (up to gauge freedom). This result is non-trivial because the equations are highly nonlinear and coupled, and for generic systems of this type, well-posedness cannot be taken for granted.</p>\n";

    // Section X.4: Technical details with equations
    $html .= "<h3>{$chNum}.4 Quantitative Results</h3>\n";

    // Add equation
    $html .= "<div class=\"eq\">S[&phi;] = &int; d<sup>4</sup>x &radic;(-g) [ R/(16&pi;G) + L<sub>matter</sub>(&phi;, &nabla;&phi;) + &alpha; R<sup>2</sup> + &beta; R<sub>&mu;&nu;</sub>R<sup>&mu;&nu;</sup> ]</div>\n";

    $html .= "<p>The action functional displayed above encodes the complete dynamics of the system under investigation. The first term is the standard Einstein-Hilbert action of general relativity, with Newton's gravitational constant G. The matter Lagrangian L_matter describes the coupling between the gravitational field and the matter degrees of freedom. The higher-curvature terms proportional to alpha and beta represent quantum corrections that become significant at the energy scales probed in our analysis.</p>\n";
    $html .= "<p>Variation of this action with respect to the metric yields the modified field equations, which we solve using both analytical and numerical methods. The analytical solutions are obtained in the weak-field regime using a systematic post-Newtonian expansion, carried to sufficiently high order (typically 5th post-Newtonian order) to achieve the required precision. The numerical solutions employ full nonlinear evolution codes based on the 3+1 decomposition of spacetime, with constraint-damping formulations that ensure long-term numerical stability.</p>\n";

    // Section X.5: Discussion
    $html .= "<h3>{$chNum}.5 Discussion and Implications</h3>\n";
    $escDept = htmlspecialchars($department);
    $html .= "<p>The results presented in this chapter have significant implications for several active areas of research within {$escDept}. First, our analysis provides new constraints on the parameter space of viable theories, ruling out a substantial region that was previously considered allowed. Specifically, we find that the coupling constants must satisfy alpha > 0 and beta < -alpha/3 for the theory to be both perturbatively stable and ghost-free at the linearized level.</p>\n";
    $html .= "<p>Second, our results predict specific observational signatures that could be detected by current or near-future experimental facilities. These predictions are quantitative rather than merely qualitative, providing precise numerical targets for experimental searches. The most accessible signature is a modification of the standard dispersion relation at high energies, which manifests as an energy-dependent propagation velocity for signals in the " . htmlspecialchars($division) . " sector.</p>\n";
    $html .= "<p>Third, the mathematical framework developed here has applications beyond the specific physical system studied in this thesis. The fiber bundle techniques and the non-perturbative methods are quite general and can be adapted to a wide range of problems in mathematical physics. We anticipate that these methods will find applications in areas ranging from condensed matter physics to quantum information theory, wherever strongly coupled quantum systems exhibit emergent geometric structure.</p>\n";

    // Add a classified note for extra flair
    $escDiv = htmlspecialchars($division);
    $html .= "<div class=\"note\"><strong>Classification Note:</strong> Sections {$chNum}.6 through {$chNum}.12, containing detailed experimental protocols, raw data tables, and classified analysis methodologies, are available only in the full {$escDiv} Archive Edition (Pantheon Clearance Level ULTRA SECRET). Total section length: approximately 25 additional pages.</div>\n";

    return $html;
}

/**
 * Build appendix with mathematical derivations and proofs
 */
function buildAppendix(array $t): string {
    $division = htmlspecialchars($t['division']);
    $department = htmlspecialchars($t['department']);
    $advisor = htmlspecialchars($t['advisor']);
    $title = htmlspecialchars($t['title']);
    $pages = (int)$t['pages'];

    $html = "<div class=\"appendix\">\n";
    $html .= "<h2>Appendix A: Mathematical Derivations</h2>\n";

    $html .= "<h3>A.1 Derivation of the Master Equation</h3>\n";
    $html .= "<p>In this appendix, we present the detailed derivation of the master equation governing {$division} dynamics, which was stated without proof in Chapter 2. The derivation proceeds in three stages: (1) establishment of the variational principle; (2) computation of the Euler-Lagrange equations; and (3) reduction to the physical degrees of freedom via gauge fixing.</p>\n";
    $html .= "<div class=\"eq\">&nabla;<sub>&mu;</sub> F<sup>&mu;&nu;</sup> + &Gamma;<sup>&nu;</sup><sub>&mu;&lambda;</sub> F<sup>&mu;&lambda;</sup> = J<sup>&nu;</sup> + &alpha; &nabla;<sub>&mu;</sub>(R F<sup>&mu;&nu;</sup>)</div>\n";
    $html .= "<p>The master equation above is obtained by varying the action with respect to the connection one-form and applying the Bianchi identity. The term proportional to alpha represents the leading-order quantum correction, which modifies the classical equations at the Planck scale. In the limit alpha approaches zero, we recover the standard equations of motion.</p>\n";

    $html .= "<h3>A.2 Proof of Convergence</h3>\n";
    $html .= "<p>We prove that the perturbative expansion employed in Chapter 4 converges uniformly on compact subsets of the parameter space. The proof uses the Cauchy-Hadamard theorem to establish a nonzero radius of convergence, then applies analytic continuation techniques to extend the result to the full physical regime. The key estimate involves bounding the growth of the n-th order coefficient by C^n / n! (rather than the naive bound C^n), which follows from the special algebraic structure of the perturbation series.</p>\n";

    $html .= "<h3>A.3 Numerical Methods and Error Analysis</h3>\n";
    $html .= "<p>The numerical simulations reported in this thesis employ a combination of finite-element methods (for the spatial discretization) and symplectic integrators (for the time evolution). The spatial mesh is adaptively refined based on a posterior error estimates derived from the residual of the field equations. The time integration uses a 6th-order Gauss-Legendre implicit Runge-Kutta method, which preserves the symplectic structure of the equations and ensures long-term energy conservation. Convergence tests show 6th-order convergence with mesh refinement, consistent with the theoretical expectations for our choice of finite element basis functions.</p>\n";

    // Classification notice
    $html .= "<h3>A.4 Classification Notice</h3>\n";
    $html .= "<div class=\"note\"><strong>PANTHEON CLASSIFICATION NOTICE</strong><br><br>";
    $html .= "This document is the <em>Declassified Summary Edition</em> of the full thesis, which comprises {$pages} pages in its complete form. ";
    $html .= "The complete edition contains additional appendices (B through G) with:<br><br>";
    $html .= "&bull; Appendix B: Complete experimental data sets and raw measurements ({$division} Division archive)<br>";
    $html .= "&bull; Appendix C: Extended proofs and mathematical lemmas<br>";
    $html .= "&bull; Appendix D: Computational source code listings<br>";
    $html .= "&bull; Appendix E: Detailed error analysis and uncertainty quantification<br>";
    $html .= "&bull; Appendix F: Comparison with classified studies from other Pantheon divisions<br>";
    $html .= "&bull; Appendix G: Technology transfer potential assessment<br><br>";
    $html .= "Access to the full edition requires Pantheon Clearance Level: ULTRA SECRET.<br>";
    $html .= "Contact: The Pantheon Archives, GoSiteMe Intelligence Division<br>";
    $html .= "Classification Authority: Commander Danny William Perez, client_id 33</div>\n";

    $html .= "</div>\n";

    // Author biography
    $html .= "<div class=\"appendix\">\n";
    $html .= "<h2>About the Author</h2>\n";
    $html .= "<p>" . htmlspecialchars($t['author']) . " received a Bachelor of Science in Physics from the Massachusetts Institute of Technology (summa cum laude) and a Master of Science in Applied Mathematics from Princeton University before joining The Pantheon Institute for doctoral research in {$division}. Under the guidance of {$advisor}, the author developed novel theoretical and experimental techniques that form the core contributions of this thesis.</p>\n";
    $html .= "<p>The author is a member of the Pantheon Luminaries Council and has received the Pantheon Excellence in Research Award. Current research interests include extensions of the framework developed in this thesis, interdisciplinary applications to other Pantheon research divisions, and the development of next-generation experimental facilities for {$division} research.</p>\n";
    $html .= "<p style=\"margin-top:30px;text-align:center;font-size:10pt;color:#888;\">— End of Declassified Summary Edition —</p>\n";
    $html .= "</div>\n";

    return $html;
}
