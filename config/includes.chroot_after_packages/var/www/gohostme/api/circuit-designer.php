<?php
/**
 * GoSiteMe Circuit Designer API
 * Interactive EDA tool for ZPE research circuits
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

session_start();
$client_id = $_SESSION['client_id'] ?? null;
$internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
$is_internal = !empty(INTERNAL_SECRET) && hash_equals(INTERNAL_SECRET, $internal);
if (!$client_id && !$is_internal) { echo json_encode(['error' => 'Auth required']); exit; }
require_once dirname(__DIR__) . '/includes/api-security.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS `circuit_designs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `design_id` VARCHAR(50) UNIQUE NOT NULL,
        `client_id` INT NOT NULL,
        `title` VARCHAR(200) NOT NULL,
        `description` TEXT,
        `category` VARCHAR(50) DEFAULT 'custom',
        `researcher` VARCHAR(100),
        `components` JSON,
        `connections` JSON,
        `canvas_state` JSON,
        `simulation_data` JSON,
        `status` ENUM('draft','simulated','tested','proven') DEFAULT 'draft',
        `is_template` TINYINT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']); exit;
}

$action = $_REQUEST['action'] ?? 'library';

switch ($action) {

// ─── Component Library ───────────────────────────────────────────
case 'library':
    echo json_encode(['success' => true, 'library' => [
        'passive' => [
            ['id'=>'resistor','name'=>'Resistor','symbol'=>'R','unit'=>'Ω','desc'=>'Limits current flow','params'=>['resistance'=>1000]],
            ['id'=>'capacitor','name'=>'Capacitor','symbol'=>'C','unit'=>'F','desc'=>'Stores electric charge','params'=>['capacitance'=>0.000001]],
            ['id'=>'inductor','name'=>'Inductor','symbol'=>'L','unit'=>'H','desc'=>'Stores energy in magnetic field','params'=>['inductance'=>0.001]],
            ['id'=>'transformer','name'=>'Transformer','symbol'=>'T','unit'=>'ratio','desc'=>'Transfers energy between circuits via electromagnetic induction','params'=>['turns_ratio'=>10,'primary_inductance'=>0.01]],
            ['id'=>'potentiometer','name'=>'Potentiometer','symbol'=>'VR','unit'=>'Ω','desc'=>'Variable resistor','params'=>['max_resistance'=>10000,'position'=>0.5]],
            ['id'=>'crystal','name'=>'Crystal Oscillator','symbol'=>'Y','unit'=>'Hz','desc'=>'Piezoelectric frequency reference','params'=>['frequency'=>1000000]],
        ],
        'active' => [
            ['id'=>'diode','name'=>'Diode','symbol'=>'D','unit'=>'V','desc'=>'One-way current gate','params'=>['forward_voltage'=>0.7]],
            ['id'=>'led','name'=>'LED','symbol'=>'LED','unit'=>'V','desc'=>'Light-emitting diode','params'=>['forward_voltage'=>2.0,'color'=>'red']],
            ['id'=>'transistor_npn','name'=>'NPN Transistor','symbol'=>'Q','unit'=>'hFE','desc'=>'Current amplifier/switch','params'=>['gain'=>100]],
            ['id'=>'transistor_pnp','name'=>'PNP Transistor','symbol'=>'Q','unit'=>'hFE','desc'=>'Current amplifier/switch (inverted)','params'=>['gain'=>100]],
            ['id'=>'mosfet_n','name'=>'N-MOSFET','symbol'=>'M','unit'=>'V','desc'=>'Voltage-controlled switch','params'=>['threshold'=>2.0]],
            ['id'=>'op_amp','name'=>'Op-Amp','symbol'=>'U','unit'=>'dB','desc'=>'Operational amplifier','params'=>['gain'=>100000,'bandwidth'=>1000000]],
        ],
        'power' => [
            ['id'=>'battery','name'=>'Battery/DC Source','symbol'=>'V','unit'=>'V','desc'=>'DC voltage source','params'=>['voltage'=>12]],
            ['id'=>'ac_source','name'=>'AC Source','symbol'=>'VAC','unit'=>'V','desc'=>'AC voltage source','params'=>['voltage'=>120,'frequency'=>60]],
            ['id'=>'ground','name'=>'Ground','symbol'=>'GND','unit'=>'','desc'=>'Circuit ground reference','params'=>[]],
            ['id'=>'antenna','name'=>'Antenna','symbol'=>'ANT','unit'=>'','desc'=>'EM radiation collector','params'=>['length'=>1.0]],
        ],
        'zpe_special' => [
            ['id'=>'spark_gap','name'=>'Spark Gap','symbol'=>'SG','unit'=>'kV','desc'=>'High-voltage discharge gap (Don Smith circuits)','params'=>['breakdown_voltage'=>5000,'gap_mm'=>2]],
            ['id'=>'neon_lamp','name'=>'Neon Indicator','symbol'=>'NE','unit'=>'V','desc'=>'Gas discharge indicator lamp','params'=>['strike_voltage'=>90]],
            ['id'=>'bifilar_coil','name'=>'Bifilar Coil','symbol'=>'BF','unit'=>'H','desc'=>'Tesla bifilar pancake coil — cancels self-inductance','params'=>['inductance'=>0.001,'turns'=>50]],
            ['id'=>'seg_ring','name'=>'SEG Ring Element','symbol'=>'SEG','unit'=>'','desc'=>'Searl Effect Generator magnetic ring','params'=>['rings'=>3,'rollers_per_ring'=>12]],
            ['id'=>'crystal_cell','name'=>'Crystal Energy Cell','symbol'=>'CC','unit'=>'V','desc'=>'Hutchison-style layered crystal battery','params'=>['layers'=>5,'voltage_per_layer'=>0.3]],
            ['id'=>'toroid','name'=>'Toroidal Core','symbol'=>'TC','unit'=>'H','desc'=>'Toroid magnetic core for flux concentration','params'=>['inner_diam'=>20,'outer_diam'=>40,'material'=>'ferrite']],
            ['id'=>'caduceus_coil','name'=>'Caduceus Coil','symbol'=>'CAD','unit'=>'H','desc'=>'Counter-wound coil — scalar wave experiments','params'=>['turns'=>100,'wire_gauge'=>18]],
        ],
        'measurement' => [
            ['id'=>'voltmeter','name'=>'Voltmeter','symbol'=>'VM','unit'=>'V','desc'=>'Measures voltage','params'=>[]],
            ['id'=>'ammeter','name'=>'Ammeter','symbol'=>'AM','unit'=>'A','desc'=>'Measures current','params'=>[]],
            ['id'=>'oscilloscope','name'=>'Oscilloscope','symbol'=>'OSC','unit'=>'','desc'=>'Waveform display','params'=>['timebase'=>0.001]],
            ['id'=>'freq_counter','name'=>'Frequency Counter','symbol'=>'FC','unit'=>'Hz','desc'=>'Measures signal frequency','params'=>[]],
        ]
    ]]);
    break;

// ─── Circuit Templates (ZPE Pre-built) ──────────────────────────
case 'templates':
    echo json_encode(['success' => true, 'templates' => [
        [
            'id' => 'don-smith-tank-v1',
            'title' => 'Don Smith Resonant Tank Circuit',
            'researcher' => 'Don Smith',
            'category' => 'resonant',
            'description' => 'L-C resonant tank tuned to ambient frequency for energy extraction. Uses spark gap excitation with step-up transformer.',
            'formula' => 'f = 1/(2π√(LC))',
            'components' => [
                ['type'=>'battery','x'=>50,'y'=>200,'params'=>['voltage'=>12],'label'=>'V1'],
                ['type'=>'spark_gap','x'=>150,'y'=>100,'params'=>['breakdown_voltage'=>5000,'gap_mm'=>2],'label'=>'SG1'],
                ['type'=>'transformer','x'=>300,'y'=>150,'params'=>['turns_ratio'=>100,'primary_inductance'=>0.001],'label'=>'T1'],
                ['type'=>'capacitor','x'=>450,'y'=>100,'params'=>['capacitance'=>0.0000001],'label'=>'C1'],
                ['type'=>'inductor','x'=>450,'y'=>250,'params'=>['inductance'=>0.01],'label'=>'L1'],
                ['type'=>'diode','x'=>550,'y'=>150,'params'=>['forward_voltage'=>0.7],'label'=>'D1'],
                ['type'=>'capacitor','x'=>650,'y'=>200,'params'=>['capacitance'=>0.001],'label'=>'C2'],
                ['type'=>'resistor','x'=>750,'y'=>200,'params'=>['resistance'=>100],'label'=>'R_LOAD'],
                ['type'=>'voltmeter','x'=>750,'y'=>100,'params'=>[],'label'=>'VM1'],
                ['type'=>'ground','x'=>400,'y'=>350,'params'=>[],'label'=>'GND'],
            ],
            'theory' => 'The spark gap generates a broad spectrum pulse that excites the LC tank at its resonant frequency. The transformer steps up the voltage. The resonant circuit acts as a frequency filter, selecting the natural resonant frequency of the environment. Energy is extracted and rectified through the diode bridge.',
        ],
        [
            'id' => 'tesla-radiant-collector',
            'title' => 'Tesla Radiant Energy Collector',
            'researcher' => 'Nikola Tesla',
            'category' => 'radiant',
            'description' => 'Based on Tesla patent US685957. Antenna collects ambient EM radiation, LC circuit tunes to resonance.',
            'formula' => 'V = E × h (antenna voltage = field strength × height)',
            'components' => [
                ['type'=>'antenna','x'=>100,'y'=>50,'params'=>['length'=>2.0],'label'=>'ANT1'],
                ['type'=>'inductor','x'=>200,'y'=>150,'params'=>['inductance'=>0.1],'label'=>'L1'],
                ['type'=>'capacitor','x'=>350,'y'=>150,'params'=>['capacitance'=>0.00001],'label'=>'C1'],
                ['type'=>'diode','x'=>450,'y'=>100,'params'=>['forward_voltage'=>0.3],'label'=>'D1'],
                ['type'=>'diode','x'=>450,'y'=>200,'params'=>['forward_voltage'=>0.3],'label'=>'D2'],
                ['type'=>'capacitor','x'=>550,'y'=>150,'params'=>['capacitance'=>0.01],'label'=>'C_STORE'],
                ['type'=>'voltmeter','x'=>650,'y'=>150,'params'=>[],'label'=>'VM1'],
                ['type'=>'ground','x'=>300,'y'=>300,'params'=>[],'label'=>'GND'],
            ],
            'theory' => 'Tesla demonstrated that elevated antennas can capture ambient electromagnetic radiation. The LC circuit tunes to maximize energy capture at the resonant frequency. A voltage doubler rectifies and stores the harvested energy.',
        ],
        [
            'id' => 'hutchison-crystal-battery',
            'title' => 'Hutchison Crystal Energy Cell',
            'researcher' => 'John Hutchison',
            'category' => 'crystal',
            'description' => 'Layered dissimilar metal crystal battery with sustained electrical potential without chemical reaction.',
            'formula' => 'V_total = n × V_layer',
            'components' => [
                ['type'=>'crystal_cell','x'=>150,'y'=>150,'params'=>['layers'=>5,'voltage_per_layer'=>0.3],'label'=>'CC1'],
                ['type'=>'crystal_cell','x'=>300,'y'=>150,'params'=>['layers'=>5,'voltage_per_layer'=>0.3],'label'=>'CC2'],
                ['type'=>'crystal_cell','x'=>450,'y'=>150,'params'=>['layers'=>5,'voltage_per_layer'=>0.3],'label'=>'CC3'],
                ['type'=>'capacitor','x'=>550,'y'=>100,'params'=>['capacitance'=>0.1],'label'=>'C_BUFFER'],
                ['type'=>'resistor','x'=>650,'y'=>200,'params'=>['resistance'=>1000],'label'=>'R_LOAD'],
                ['type'=>'voltmeter','x'=>650,'y'=>100,'params'=>[],'label'=>'VM1'],
                ['type'=>'ammeter','x'=>550,'y'=>250,'params'=>[],'label'=>'AM1'],
                ['type'=>'ground','x'=>400,'y'=>300,'params'=>[],'label'=>'GND'],
            ],
            'theory' => 'Hutchison-style crystal cells use dissimilar metal layers with crystalline structures to generate sustained electrical potential. Series connection of multiple cells increases total voltage. The mechanism is theorized to tap into lattice energy or quantum vacuum fluctuations.',
        ],
        [
            'id' => 'searl-seg-mini',
            'title' => 'Searl Effect Generator (Mini)',
            'researcher' => 'Professor John Searl',
            'category' => 'magnetic',
            'description' => 'Miniature SEG with 3 concentric magnetic rings and rollers demonstrating the Searl Effect.',
            'formula' => 'Rollers follow Law of Squares: n² pattern imprinting',
            'components' => [
                ['type'=>'seg_ring','x'=>300,'y'=>200,'params'=>['rings'=>3,'rollers_per_ring'=>12],'label'=>'SEG1'],
                ['type'=>'bifilar_coil','x'=>500,'y'=>100,'params'=>['inductance'=>0.01,'turns'=>200],'label'=>'PICKUP1'],
                ['type'=>'bifilar_coil','x'=>500,'y'=>300,'params'=>['inductance'=>0.01,'turns'=>200],'label'=>'PICKUP2'],
                ['type'=>'capacitor','x'=>600,'y'=>200,'params'=>['capacitance'=>0.001],'label'=>'C1'],
                ['type'=>'diode','x'=>650,'y'=>150,'params'=>['forward_voltage'=>0.7],'label'=>'D1'],
                ['type'=>'diode','x'=>650,'y'=>250,'params'=>['forward_voltage'=>0.7],'label'=>'D2'],
                ['type'=>'voltmeter','x'=>750,'y'=>200,'params'=>[],'label'=>'VM1'],
                ['type'=>'ground','x'=>400,'y'=>350,'params'=>[],'label'=>'GND'],
            ],
            'theory' => 'The SEG uses neodymium magnetic rings with rollers magnetized according to the Law of Squares. When rollers begin orbital motion, they generate electrical current in pickup coils. The device is theorized to produce both electrical energy and a gravitational reduction effect (inverse-G).',
        ],
    ]]);
    break;

// ─── Save Circuit Design ────────────────────────────────────────
case 'save':
    $title = trim($_POST['title'] ?? '');
    $components = $_POST['components'] ?? '[]';
    $connections = $_POST['connections'] ?? '[]';
    $canvas_state = $_POST['canvas_state'] ?? '{}';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? 'custom';
    $researcher = $_POST['researcher'] ?? '';
    $design_id = $_POST['design_id'] ?? ('CKT-' . strtoupper(substr(md5(uniqid('', true)), 0, 10)));

    if (empty($title)) { echo json_encode(['error' => 'Title required']); exit; }

    // Validate JSON
    json_decode($components); if (json_last_error() !== JSON_ERROR_NONE) { echo json_encode(['error' => 'Invalid components JSON']); exit; }
    json_decode($connections); if (json_last_error() !== JSON_ERROR_NONE) { echo json_encode(['error' => 'Invalid connections JSON']); exit; }

    $uid = $client_id ?: 1;
    $stmt = $pdo->prepare("INSERT INTO circuit_designs (design_id, client_id, title, description, category, researcher, components, connections, canvas_state) 
        VALUES (?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), components=VALUES(components), connections=VALUES(connections), canvas_state=VALUES(canvas_state), updated_at=NOW()");
    $stmt->execute([$design_id, $uid, $title, $description, $category, $researcher, $components, $connections, $canvas_state]);

    echo json_encode(['success' => true, 'design_id' => $design_id, 'message' => "Circuit '{$title}' saved"]);
    break;

// ─── Load Circuit Design ────────────────────────────────────────
case 'load':
    $design_id = $_GET['design_id'] ?? '';
    if (empty($design_id)) { echo json_encode(['error' => 'design_id required']); exit; }
    $stmt = $pdo->prepare("SELECT * FROM circuit_designs WHERE design_id = ?");
    $stmt->execute([$design_id]);
    $design = $stmt->fetch();
    if (!$design) { echo json_encode(['error' => 'Design not found']); exit; }
    echo json_encode(['success' => true, 'design' => $design]);
    break;

// ─── List Saved Designs ─────────────────────────────────────────
case 'designs':
    $uid = $client_id ?: 1;
    $stmt = $pdo->prepare("SELECT design_id, title, description, category, researcher, status, created_at, updated_at FROM circuit_designs WHERE client_id = ? OR is_template = 1 ORDER BY updated_at DESC");
    $stmt->execute([$uid]);
    echo json_encode(['success' => true, 'designs' => $stmt->fetchAll()]);
    break;

// ─── Simple Circuit Simulation ───────────────────────────────────
case 'simulate':
    $components = json_decode($_POST['components'] ?? '[]', true);
    $connections = json_decode($_POST['connections'] ?? '[]', true);

    if (empty($components)) { echo json_encode(['error' => 'No components to simulate']); exit; }

    $results = simulateCircuit($components, $connections);
    echo json_encode(['success' => true, 'simulation' => $results]);
    break;

// ─── Delete Design ───────────────────────────────────────────────
case 'delete':
    $design_id = $_POST['design_id'] ?? '';
    $uid = $client_id ?: 1;
    $stmt = $pdo->prepare("DELETE FROM circuit_designs WHERE design_id = ? AND client_id = ? AND is_template = 0");
    $stmt->execute([$design_id, $uid]);
    echo json_encode(['success' => $stmt->rowCount() > 0, 'message' => $stmt->rowCount() ? 'Deleted' : 'Not found or is template']);
    break;

default:
    echo json_encode(['error' => 'Unknown action', 'actions' => ['library','templates','save','load','designs','simulate','delete']]);
}

// ─── Simple Circuit Simulator ────────────────────────────────────
function simulateCircuit(array $components, array $connections): array {
    $voltage_sources = [];
    $resistors = [];
    $capacitors = [];
    $inductors = [];
    $total_resistance = 0;
    $total_capacitance = 0;
    $total_inductance = 0;
    $source_voltage = 0;
    $source_frequency = 60;

    foreach ($components as $c) {
        $type = $c['type'] ?? '';
        $params = $c['params'] ?? [];
        switch ($type) {
            case 'battery':
                $source_voltage += $params['voltage'] ?? 0;
                break;
            case 'ac_source':
                $source_voltage = $params['voltage'] ?? 120;
                $source_frequency = $params['frequency'] ?? 60;
                break;
            case 'resistor':
            case 'potentiometer':
                $r = $params['resistance'] ?? $params['max_resistance'] ?? 1000;
                if ($type === 'potentiometer') $r *= ($params['position'] ?? 0.5);
                $total_resistance += $r;
                $resistors[] = $r;
                break;
            case 'capacitor':
                $c_val = $params['capacitance'] ?? 0.000001;
                $total_capacitance += $c_val;
                $capacitors[] = $c_val;
                break;
            case 'inductor':
            case 'bifilar_coil':
                $l = $params['inductance'] ?? 0.001;
                $total_inductance += $l;
                $inductors[] = $l;
                break;
            case 'crystal_cell':
                $layers = $params['layers'] ?? 5;
                $vperlayer = $params['voltage_per_layer'] ?? 0.3;
                $source_voltage += $layers * $vperlayer;
                break;
            case 'spark_gap':
                // Spark gap acts as a switch at breakdown voltage
                break;
        }
    }

    $results = [
        'source_voltage' => $source_voltage,
        'total_resistance' => $total_resistance,
        'total_capacitance' => $total_capacitance,
        'total_inductance' => $total_inductance,
    ];

    // Ohm's Law
    if ($total_resistance > 0 && $source_voltage > 0) {
        $results['current'] = $source_voltage / $total_resistance;
        $results['power'] = $source_voltage * $results['current'];
    }

    // Resonant frequency
    if ($total_inductance > 0 && $total_capacitance > 0) {
        $results['resonant_frequency'] = 1 / (2 * M_PI * sqrt($total_inductance * $total_capacitance));
        $results['resonant_frequency_formatted'] = formatFreq($results['resonant_frequency']);

        // Impedance at resonance
        $results['impedance_at_resonance'] = sqrt($total_inductance / $total_capacitance);

        // Q factor
        if ($total_resistance > 0) {
            $results['q_factor'] = $results['impedance_at_resonance'] / $total_resistance;
        }

        // Wavelength at resonance
        $results['wavelength'] = 299792458 / $results['resonant_frequency'];
    }

    // Reactances
    if ($total_capacitance > 0) {
        $results['capacitive_reactance'] = 1 / (2 * M_PI * $source_frequency * $total_capacitance);
    }
    if ($total_inductance > 0) {
        $results['inductive_reactance'] = 2 * M_PI * $source_frequency * $total_inductance;
    }

    // Time constant
    if ($total_resistance > 0 && $total_capacitance > 0) {
        $results['rc_time_constant'] = $total_resistance * $total_capacitance;
    }
    if ($total_resistance > 0 && $total_inductance > 0) {
        $results['rl_time_constant'] = $total_inductance / $total_resistance;
    }

    $results['component_count'] = count($components);
    $results['connection_count'] = count($connections);

    return $results;
}

function formatFreq(float $f): string {
    if ($f >= 1e9) return round($f/1e9, 2) . ' GHz';
    if ($f >= 1e6) return round($f/1e6, 2) . ' MHz';
    if ($f >= 1e3) return round($f/1e3, 2) . ' kHz';
    return round($f, 2) . ' Hz';
}
