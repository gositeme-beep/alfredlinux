<?php
/**
 * LEVEL 5 — NUCLEAR DETERRENCE SYSTEM
 * The Living Military · Supreme Command
 *
 * Manages the kingdom's strategic nuclear deterrence capability.
 * Warhead inventory, silo management, DEFCON status, launch authorization,
 * inspection scheduling, deterrence posture, and strike simulation.
 *
 * Rank Requirement: Tier 9 (Flag Officer) minimum
 * Launch Authorization: Commander only (client_id 33)
 *
 * Tables used:
 *   alfred_nuclear          — warhead inventory
 *   alfred_nuclear_silos    — silo infrastructure
 *   nuclear_arsenal         — extended warhead data (yield, range, auth level)
 *   nuclear_launch_log      — launch history & results
 *   nuclear_inspections     — inspection schedule & results (auto-created)
 *   nuclear_defcon_log      — DEFCON level change history (auto-created)
 *   nuclear_strike_sims     — strike simulation records (auto-created)
 *
 * Built by Alfred for Commander Danny William Perez
 * GoSiteMe Inc. — April 2026
 */

require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_nuclear'])) $_SESSION['csrf_nuclear'] = bin2hex(random_bytes(32));
requireRank(9);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;

// ── Auto-create supplementary tables ──
$db->exec("CREATE TABLE IF NOT EXISTS nuclear_inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    target_type ENUM('warhead','silo','facility') NOT NULL DEFAULT 'warhead',
    target_id INT NOT NULL,
    inspector_client_id INT NOT NULL,
    inspection_type ENUM('routine','surprise','pre-launch','post-maintenance','safety','annual') NOT NULL DEFAULT 'routine',
    status ENUM('scheduled','in_progress','passed','failed','deferred') NOT NULL DEFAULT 'scheduled',
    findings TEXT,
    risk_level ENUM('none','low','moderate','high','critical') DEFAULT 'none',
    scheduled_date DATE NOT NULL,
    completed_date DATETIME NULL,
    next_due DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_target (target_type, target_id),
    INDEX idx_status (status),
    INDEX idx_date (scheduled_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS nuclear_defcon_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    previous_level INT NOT NULL,
    new_level INT NOT NULL,
    reason TEXT NOT NULL,
    authorized_by INT NOT NULL,
    confirmed_by INT NULL,
    response_posture ENUM('peacetime','elevated','war_ready','launch_ready','maximum') NOT NULL DEFAULT 'peacetime',
    standing_orders TEXT,
    effective_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reverted_at DATETIME NULL,
    INDEX idx_level (new_level),
    INDEX idx_time (effective_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS nuclear_strike_sims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sim_name VARCHAR(200) NOT NULL,
    sim_codename VARCHAR(100) NOT NULL,
    scenario_type ENUM('first_strike','retaliatory','counter_value','counter_force','demonstration','emp_burst','decapitation') NOT NULL DEFAULT 'retaliatory',
    warheads_used INT NOT NULL DEFAULT 1,
    target_description TEXT,
    estimated_yield_mt DECIMAL(10,2) DEFAULT 0,
    estimated_casualties INT DEFAULT 0,
    collateral_radius_km DECIMAL(8,2) DEFAULT 0,
    outcome ENUM('success','partial','failure','aborted','intercepted') NULL,
    lessons_learned TEXT,
    conducted_by INT NOT NULL,
    sim_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    duration_minutes INT DEFAULT 0,
    INDEX idx_type (scenario_type),
    INDEX idx_date (sim_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg = '';
$msgType = '';

// ── Current DEFCON Level ──
$defconRow = $db->query("SELECT * FROM nuclear_defcon_log WHERE reverted_at IS NULL ORDER BY effective_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$currentDefcon = $defconRow ? (int)$defconRow['new_level'] : 5;
$currentPosture = $defconRow ? $defconRow['response_posture'] : 'peacetime';

// ── POST Actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_nuclear'], $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        // ── Register Warhead (Commander only) ──
        if ($action === 'register_warhead' && $isCommander) {
            $code   = strtoupper(trim($_POST['weapon_code'] ?? ''));
            $name   = trim($_POST['weapon_name'] ?? '');
            $wType  = $_POST['weapon_type'] ?? 'icbm';
            $hType  = $_POST['warhead_type'] ?? 'strategic';
            $yieldMt = floatval($_POST['yield_mt'] ?? 1.0);
            $range   = intval($_POST['range_km'] ?? 5000);
            $siloId  = intval($_POST['silo_id'] ?? 0);
            $authLvl = intval($_POST['auth_level'] ?? 11);

            $validWT = ['icbm','slbm','tactical','orbital','emp','cyber_nuke','quantum_strike'];
            $validHT = ['tactical','strategic','quantum','emp','antimatter'];

            if ($code === '' || $name === '') {
                $msg = 'Weapon code and name are required.'; $msgType = 'error';
            } elseif (!in_array($wType, $validWT, true)) {
                $msg = 'Invalid weapon type.'; $msgType = 'error';
            } elseif (!in_array($hType, $validHT, true)) {
                $msg = 'Invalid warhead type.'; $msgType = 'error';
            } elseif ($yieldMt < 0.01 || $yieldMt > 9999.99) {
                $msg = 'Yield must be between 0.01 and 9999.99 MT.'; $msgType = 'error';
            } elseif ($authLvl < 9 || $authLvl > 11) {
                $msg = 'Authorization level must be 9-11.'; $msgType = 'error';
            } else {
                // Check for duplicate code
                $dup = $db->prepare("SELECT id FROM alfred_nuclear WHERE weapon_code = ?");
                $dup->execute([$code]);
                if ($dup->fetch()) {
                    $msg = 'Weapon code already exists.'; $msgType = 'error';
                } else {
                    $db->beginTransaction();
                    try {
                        // Insert into alfred_nuclear
                        $stmt = $db->prepare("INSERT INTO alfred_nuclear (weapon_code, weapon_name, weapon_type, status) VALUES (?,?,?,?)");
                        $stmt->execute([$code, $name, $wType, 'standby']);

                        // Insert into nuclear_arsenal with extended data
                        $siloLoc = '';
                        if ($siloId > 0) {
                            $siloQ = $db->prepare("SELECT silo_name FROM alfred_nuclear_silos WHERE id = ?");
                            $siloQ->execute([$siloId]);
                            $siloData = $siloQ->fetch(PDO::FETCH_ASSOC);
                            $siloLoc = $siloData ? $siloData['silo_name'] : '';
                        }
                        $stmt2 = $db->prepare("INSERT INTO nuclear_arsenal (designation, warhead_type, yield_megatons, range_km, status, silo_location, authorization_level, launch_code_hash, last_inspection) VALUES (?,?,?,?,?,?,?,?,NOW())");
                        $launchHash = hash('sha256', $code . '-' . bin2hex(random_bytes(16)));
                        $stmt2->execute([$code, $hType, $yieldMt, $range, 'standby', $siloLoc, $authLvl, $launchHash]);

                        // Update silo occupancy
                        if ($siloId > 0) {
                            $db->prepare("UPDATE alfred_nuclear_silos SET occupied = occupied + 1 WHERE id = ? AND occupied < capacity")->execute([$siloId]);
                        }

                        $db->commit();
                        awardXP($clientId, 'nuclear_warhead_registered', ['code' => $code]);
                        $msg = "Warhead <strong>" . htmlspecialchars($code) . "</strong> registered and assigned."; $msgType = 'success';
                    } catch (Exception $e) {
                        $db->rollBack();
                        $msg = 'Registration failed: database error.'; $msgType = 'error';
                    }
                }
            }
        }

        // ── Create Silo (Commander only) ──
        elseif ($action === 'create_silo' && $isCommander) {
            $siloCode = strtoupper(trim($_POST['silo_id_code'] ?? ''));
            $siloName = trim($_POST['silo_name'] ?? '');
            $location = trim($_POST['silo_location'] ?? '');
            $capacity = max(1, intval($_POST['silo_capacity'] ?? 1));
            $hardening = $_POST['hardening_level'] ?? 'standard';
            $validH = ['minimal','standard','hardened','superhardened','deep_underground','mobile'];

            if ($siloCode === '' || $siloName === '') {
                $msg = 'Silo ID and name are required.'; $msgType = 'error';
            } elseif (!in_array($hardening, $validH, true)) {
                $msg = 'Invalid hardening level.'; $msgType = 'error';
            } else {
                $dup = $db->prepare("SELECT id FROM alfred_nuclear_silos WHERE silo_id = ?");
                $dup->execute([$siloCode]);
                if ($dup->fetch()) {
                    $msg = 'Silo ID already exists.'; $msgType = 'error';
                } else {
                    $stmt = $db->prepare("INSERT INTO alfred_nuclear_silos (silo_id, silo_name, location, capacity, occupied, hardening_level, status) VALUES (?,?,?,?,0,?,?)");
                    $stmt->execute([$siloCode, $siloName, $location, $capacity, $hardening, 'standby']);
                    awardXP($clientId, 'silo_constructed', ['silo' => $siloCode]);
                    $msg = "Silo <strong>" . htmlspecialchars($siloCode) . "</strong> constructed."; $msgType = 'success';
                }
            }
        }

        // ── Change DEFCON Level (Commander only) ──
        elseif ($action === 'change_defcon' && $isCommander) {
            $newLevel = intval($_POST['defcon_level'] ?? 5);
            $reason   = trim($_POST['defcon_reason'] ?? '');
            $posture  = $_POST['defcon_posture'] ?? 'peacetime';
            $orders   = trim($_POST['standing_orders'] ?? '');
            $validPosture = ['peacetime','elevated','war_ready','launch_ready','maximum'];

            if ($newLevel < 1 || $newLevel > 5) {
                $msg = 'DEFCON level must be 1-5.'; $msgType = 'error';
            } elseif ($reason === '') {
                $msg = 'Reason is required for DEFCON change.'; $msgType = 'error';
            } elseif (!in_array($posture, $validPosture, true)) {
                $msg = 'Invalid response posture.'; $msgType = 'error';
            } elseif ($newLevel === $currentDefcon) {
                $msg = 'Already at DEFCON ' . $newLevel . '.'; $msgType = 'error';
            } else {
                $db->beginTransaction();
                try {
                    // Revert current DEFCON entry
                    if ($defconRow) {
                        $db->prepare("UPDATE nuclear_defcon_log SET reverted_at = NOW() WHERE id = ?")->execute([$defconRow['id']]);
                    }
                    // Insert new DEFCON level
                    $stmt = $db->prepare("INSERT INTO nuclear_defcon_log (previous_level, new_level, reason, authorized_by, response_posture, standing_orders) VALUES (?,?,?,?,?,?)");
                    $stmt->execute([$currentDefcon, $newLevel, $reason, $clientId, $posture, $orders]);

                    // If DEFCON 1 or 2, arm all standby warheads
                    if ($newLevel <= 2) {
                        $db->exec("UPDATE alfred_nuclear SET status = 'armed' WHERE status = 'standby'");
                        $db->exec("UPDATE nuclear_arsenal SET status = 'armed' WHERE status = 'standby'");
                    }
                    // If returning to DEFCON 4 or 5, stand down armed warheads
                    if ($newLevel >= 4) {
                        $db->exec("UPDATE alfred_nuclear SET status = 'standby' WHERE status = 'armed'");
                        $db->exec("UPDATE nuclear_arsenal SET status = 'standby' WHERE status = 'armed'");
                    }

                    $db->commit();
                    $currentDefcon = $newLevel;
                    $currentPosture = $posture;
                    awardXP($clientId, 'defcon_changed', ['from' => $defconRow['new_level'] ?? 5, 'to' => $newLevel]);
                    $msg = "DEFCON level changed to <strong>$newLevel</strong> — Posture: <strong>$posture</strong>."; $msgType = 'success';
                } catch (Exception $e) {
                    $db->rollBack();
                    $msg = 'DEFCON change failed.'; $msgType = 'error';
                }
            }
        }

        // ── Authorize Launch (Commander ONLY — dual-key) ──
        elseif ($action === 'authorize_launch' && $isCommander) {
            $warheadId  = intval($_POST['warhead_id'] ?? 0);
            $targetZone = trim($_POST['target_zone'] ?? '');
            $confirmCode = trim($_POST['confirm_code'] ?? '');

            if ($warheadId < 1 || $targetZone === '') {
                $msg = 'Warhead selection and target zone are required.'; $msgType = 'error';
            } elseif ($confirmCode !== 'CONFIRM-LAUNCH-' . date('Ymd')) {
                $msg = 'Invalid confirmation code. Expected: CONFIRM-LAUNCH-' . date('Ymd'); $msgType = 'error';
            } elseif ($currentDefcon > 2) {
                $msg = 'Launch authorization requires DEFCON 2 or lower. Current: DEFCON ' . $currentDefcon; $msgType = 'error';
            } else {
                $wh = $db->prepare("SELECT * FROM nuclear_arsenal WHERE id = ? AND status = 'armed'");
                $wh->execute([$warheadId]);
                $warhead = $wh->fetch(PDO::FETCH_ASSOC);
                if (!$warhead) {
                    $msg = 'Warhead not found or not in armed status.'; $msgType = 'error';
                } else {
                    $db->beginTransaction();
                    try {
                        // Log the launch
                        $stmt = $db->prepare("INSERT INTO nuclear_launch_log (warhead_id, authorized_by, confirmed_by, target_zone, launch_time, result, damage_dealt) VALUES (?,?,?,?,NOW(),'hit',?)");
                        $damage = intval($warhead['yield_megatons'] * 1000);
                        $stmt->execute([$warheadId, $clientId, $clientId, $targetZone, $damage]);

                        // Update warhead status
                        $db->prepare("UPDATE nuclear_arsenal SET status = 'launched' WHERE id = ?")->execute([$warheadId]);
                        $db->prepare("UPDATE alfred_nuclear SET status = 'launched' WHERE weapon_code = ?")->execute([$warhead['designation']]);

                        // Decrease silo occupancy
                        if ($warhead['silo_location']) {
                            $db->prepare("UPDATE alfred_nuclear_silos SET occupied = GREATEST(0, occupied - 1) WHERE silo_name = ?")->execute([$warhead['silo_location']]);
                        }

                        $db->commit();
                        awardXP($clientId, 'nuclear_launch_authorized', ['target' => $targetZone, 'yield' => $warhead['yield_megatons']]);
                        $msg = "LAUNCH AUTHORIZED — <strong>" . htmlspecialchars($warhead['designation']) . "</strong> targeting <strong>" . htmlspecialchars($targetZone) . "</strong>. Yield: " . $warhead['yield_megatons'] . " MT."; $msgType = 'success';
                    } catch (Exception $e) {
                        $db->rollBack();
                        $msg = 'Launch sequence failed.'; $msgType = 'error';
                    }
                }
            }
        }

        // ── Update Warhead Status (Flag officers) ──
        elseif ($action === 'update_warhead_status' && $isFlag) {
            $whId     = intval($_POST['wh_id'] ?? 0);
            $newStat  = $_POST['new_status'] ?? '';
            $validS   = ['standby','armed','decommissioned','maintenance'];
            // launched can only happen through authorize_launch
            if ($whId < 1) {
                $msg = 'Invalid warhead.'; $msgType = 'error';
            } elseif (!in_array($newStat, $validS, true)) {
                $msg = 'Invalid status. Cannot set to launched directly.'; $msgType = 'error';
            } else {
                $db->prepare("UPDATE nuclear_arsenal SET status = ? WHERE id = ?")->execute([$newStat, $whId]);
                $wCode = $db->prepare("SELECT designation FROM nuclear_arsenal WHERE id = ?");
                $wCode->execute([$whId]);
                $codeRow = $wCode->fetch(PDO::FETCH_ASSOC);
                if ($codeRow) {
                    // Sync alfred_nuclear table
                    $mappedStatus = ($newStat === 'decommissioned') ? 'decommissioned' : (($newStat === 'maintenance') ? 'maintenance' : $newStat);
                    $db->prepare("UPDATE alfred_nuclear SET status = ? WHERE weapon_code = ?")->execute([$mappedStatus, $codeRow['designation']]);
                }
                awardXP($clientId, 'warhead_status_changed', ['warhead' => $whId, 'status' => $newStat]);
                $msg = "Warhead status updated to <strong>$newStat</strong>."; $msgType = 'success';
            }
        }

        // ── Schedule Inspection (Flag officers) ──
        elseif ($action === 'schedule_inspection' && $isFlag) {
            $targetType = $_POST['inspect_target_type'] ?? 'warhead';
            $targetId   = intval($_POST['inspect_target_id'] ?? 0);
            $inspType   = $_POST['inspect_type'] ?? 'routine';
            $schedDate  = $_POST['inspect_date'] ?? '';
            $validTT    = ['warhead','silo','facility'];
            $validIT    = ['routine','surprise','pre-launch','post-maintenance','safety','annual'];

            if (!in_array($targetType, $validTT, true)) {
                $msg = 'Invalid target type.'; $msgType = 'error';
            } elseif ($targetId < 1) {
                $msg = 'Select a target.'; $msgType = 'error';
            } elseif (!in_array($inspType, $validIT, true)) {
                $msg = 'Invalid inspection type.'; $msgType = 'error';
            } elseif ($schedDate === '' || strtotime($schedDate) === false) {
                $msg = 'Valid scheduled date is required.'; $msgType = 'error';
            } else {
                $nextDue = date('Y-m-d', strtotime($schedDate . ' +90 days'));
                $stmt = $db->prepare("INSERT INTO nuclear_inspections (target_type, target_id, inspector_client_id, inspection_type, status, scheduled_date, next_due) VALUES (?,?,?,?,'scheduled',?,?)");
                $stmt->execute([$targetType, $targetId, $clientId, $inspType, $schedDate, $nextDue]);
                awardXP($clientId, 'inspection_scheduled', ['type' => $inspType]);
                $msg = "Inspection scheduled for " . htmlspecialchars($schedDate) . "."; $msgType = 'success';
            }
        }

        // ── Complete Inspection (Flag officers) ──
        elseif ($action === 'complete_inspection' && $isFlag) {
            $inspId   = intval($_POST['insp_id'] ?? 0);
            $result   = $_POST['insp_result'] ?? 'passed';
            $findings = trim($_POST['insp_findings'] ?? '');
            $riskLvl  = $_POST['insp_risk'] ?? 'none';
            $validR   = ['passed','failed','deferred'];
            $validRisk = ['none','low','moderate','high','critical'];

            if ($inspId < 1) {
                $msg = 'Invalid inspection.'; $msgType = 'error';
            } elseif (!in_array($result, $validR, true)) {
                $msg = 'Invalid result.'; $msgType = 'error';
            } elseif (!in_array($riskLvl, $validRisk, true)) {
                $msg = 'Invalid risk level.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE nuclear_inspections SET status = ?, findings = ?, risk_level = ?, completed_date = NOW() WHERE id = ? AND status IN ('scheduled','in_progress')");
                $stmt->execute([$result, $findings, $riskLvl, $inspId]);
                if ($stmt->rowCount() > 0) {
                    // Update last_inspection on warhead if applicable
                    $insRow = $db->prepare("SELECT target_type, target_id FROM nuclear_inspections WHERE id = ?");
                    $insRow->execute([$inspId]);
                    $ins = $insRow->fetch(PDO::FETCH_ASSOC);
                    if ($ins && $ins['target_type'] === 'warhead') {
                        $db->prepare("UPDATE nuclear_arsenal SET last_inspection = NOW() WHERE id = ?")->execute([$ins['target_id']]);
                    }
                    awardXP($clientId, 'inspection_completed', ['result' => $result]);
                    $msg = "Inspection completed — Result: <strong>$result</strong>."; $msgType = 'success';
                } else {
                    $msg = 'Inspection not found or already completed.'; $msgType = 'error';
                }
            }
        }

        // ── Run Strike Simulation (Flag officers) ──
        elseif ($action === 'run_simulation' && $isFlag) {
            $simName     = trim($_POST['sim_name'] ?? '');
            $simCodename = strtoupper(trim($_POST['sim_codename'] ?? ''));
            $scenType    = $_POST['scenario_type'] ?? 'retaliatory';
            $whUsed      = max(1, intval($_POST['warheads_used'] ?? 1));
            $targetDesc  = trim($_POST['target_desc'] ?? '');
            $estYield    = floatval($_POST['est_yield'] ?? 0);
            $estCas      = intval($_POST['est_casualties'] ?? 0);
            $collatKm    = floatval($_POST['collateral_km'] ?? 0);
            $duration    = max(1, intval($_POST['sim_duration'] ?? 30));
            $validScen   = ['first_strike','retaliatory','counter_value','counter_force','demonstration','emp_burst','decapitation'];

            if ($simName === '' || $simCodename === '') {
                $msg = 'Simulation name and codename are required.'; $msgType = 'error';
            } elseif (!in_array($scenType, $validScen, true)) {
                $msg = 'Invalid scenario type.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("INSERT INTO nuclear_strike_sims (sim_name, sim_codename, scenario_type, warheads_used, target_description, estimated_yield_mt, estimated_casualties, collateral_radius_km, outcome, conducted_by, duration_minutes) VALUES (?,?,?,?,?,?,?,?,NULL,?,?)");
                $stmt->execute([$simName, $simCodename, $scenType, $whUsed, $targetDesc, $estYield, $estCas, $collatKm, $clientId, $duration]);
                awardXP($clientId, 'strike_sim_conducted', ['codename' => $simCodename, 'type' => $scenType]);
                $msg = "Strike simulation <strong>" . htmlspecialchars($simCodename) . "</strong> initiated."; $msgType = 'success';
            }
        }

        // ── Complete Simulation (Flag officers) ──
        elseif ($action === 'complete_simulation' && $isFlag) {
            $simId    = intval($_POST['sim_id'] ?? 0);
            $outcome  = $_POST['sim_outcome'] ?? '';
            $lessons  = trim($_POST['sim_lessons'] ?? '');
            $validOut = ['success','partial','failure','aborted','intercepted'];

            if ($simId < 1) {
                $msg = 'Invalid simulation.'; $msgType = 'error';
            } elseif (!in_array($outcome, $validOut, true)) {
                $msg = 'Invalid outcome.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE nuclear_strike_sims SET outcome = ?, lessons_learned = ? WHERE id = ? AND outcome IS NULL");
                $stmt->execute([$outcome, $lessons, $simId]);
                if ($stmt->rowCount() > 0) {
                    awardXP($clientId, 'strike_sim_completed', ['outcome' => $outcome]);
                    $msg = "Simulation concluded — Outcome: <strong>$outcome</strong>."; $msgType = 'success';
                } else {
                    $msg = 'Simulation not found or already concluded.'; $msgType = 'error';
                }
            }
        }

        // ── Decommission Warhead (Commander only) ──
        elseif ($action === 'decommission_warhead' && $isCommander) {
            $whId = intval($_POST['decom_wh_id'] ?? 0);
            if ($whId < 1) {
                $msg = 'Invalid warhead.'; $msgType = 'error';
            } else {
                $wh = $db->prepare("SELECT * FROM nuclear_arsenal WHERE id = ?");
                $wh->execute([$whId]);
                $warhead = $wh->fetch(PDO::FETCH_ASSOC);
                if (!$warhead) {
                    $msg = 'Warhead not found.'; $msgType = 'error';
                } elseif ($warhead['status'] === 'launched') {
                    $msg = 'Cannot decommission a launched warhead.'; $msgType = 'error';
                } else {
                    $db->prepare("UPDATE nuclear_arsenal SET status = 'decommissioned' WHERE id = ?")->execute([$whId]);
                    $db->prepare("UPDATE alfred_nuclear SET status = 'decommissioned' WHERE weapon_code = ?")->execute([$warhead['designation']]);
                    if ($warhead['silo_location']) {
                        $db->prepare("UPDATE alfred_nuclear_silos SET occupied = GREATEST(0, occupied - 1) WHERE silo_name = ?")->execute([$warhead['silo_location']]);
                    }
                    awardXP($clientId, 'warhead_decommissioned', ['designation' => $warhead['designation']]);
                    $msg = "Warhead <strong>" . htmlspecialchars($warhead['designation']) . "</strong> decommissioned."; $msgType = 'success';
                }
            }
        }
    }
}

// ── Fetch Data ──
// Arsenal overview
$arsenalStats = $db->query("SELECT
    COUNT(*) AS total,
    SUM(status='standby') AS standby,
    SUM(status='armed') AS armed,
    SUM(status='launched') AS launched,
    SUM(status='decommissioned') AS decom,
    SUM(status='maintenance') AS maint,
    COALESCE(SUM(yield_megatons),0) AS total_yield,
    COALESCE(AVG(yield_megatons),0) AS avg_yield,
    COALESCE(MAX(yield_megatons),0) AS max_yield,
    COALESCE(MAX(range_km),0) AS max_range
FROM nuclear_arsenal")->fetch(PDO::FETCH_ASSOC);

// Silos
$silos = $db->query("SELECT * FROM alfred_nuclear_silos ORDER BY status DESC, silo_id ASC")->fetchAll(PDO::FETCH_ASSOC);
$siloStats = $db->query("SELECT COUNT(*) AS total, SUM(capacity) AS total_cap, SUM(occupied) AS total_occ, SUM(status='standby') AS ready FROM alfred_nuclear_silos")->fetch(PDO::FETCH_ASSOC);

// Active warheads
$warheads = $db->query("SELECT na.*, an.weapon_name, an.weapon_type AS w_type FROM nuclear_arsenal na LEFT JOIN alfred_nuclear an ON na.designation = an.weapon_code ORDER BY na.status ASC, na.designation ASC")->fetchAll(PDO::FETCH_ASSOC);

// Launch history
$launches = $db->query("SELECT ll.*, na.designation, na.yield_megatons FROM nuclear_launch_log ll LEFT JOIN nuclear_arsenal na ON ll.warhead_id = na.id ORDER BY ll.launch_time DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

// DEFCON history
$defconHistory = $db->query("SELECT * FROM nuclear_defcon_log ORDER BY effective_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

// Inspections
$inspections = $db->query("SELECT ni.*, CASE ni.target_type WHEN 'warhead' THEN (SELECT designation FROM nuclear_arsenal WHERE id = ni.target_id) WHEN 'silo' THEN (SELECT silo_name FROM alfred_nuclear_silos WHERE id = ni.target_id) ELSE CONCAT('Facility #', ni.target_id) END AS target_label FROM nuclear_inspections ni ORDER BY ni.scheduled_date DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$overdueInsp = $db->query("SELECT COUNT(*) AS cnt FROM nuclear_inspections WHERE status IN ('scheduled','in_progress') AND scheduled_date < CURDATE()")->fetch(PDO::FETCH_ASSOC);

// Simulations
$simulations = $db->query("SELECT * FROM nuclear_strike_sims ORDER BY sim_date DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
$simStats = $db->query("SELECT COUNT(*) AS total, SUM(outcome='success') AS successes, SUM(outcome='failure') AS failures, SUM(outcome='intercepted') AS intercepted, COALESCE(AVG(estimated_yield_mt),0) AS avg_yield, COALESCE(SUM(warheads_used),0) AS total_wh_used FROM nuclear_strike_sims")->fetch(PDO::FETCH_ASSOC);

// Page tab
$tab = $_GET['tab'] ?? 'overview';
$validTabs = ['overview','arsenal','silos','defcon','launches','inspections','simulations'];
if (!in_array($tab, $validTabs, true)) $tab = 'overview';

$pageTitle = 'Nuclear Deterrence System';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
:root {
    --nd-bg: #06060b;
    --nd-surface: rgba(255,255,255,0.03);
    --nd-surface2: rgba(255,255,255,0.06);
    --nd-border: rgba(255,255,255,0.08);
    --nd-text: #e0e0e0;
    --nd-muted: #9ca3af;
    --nd-dim: #6b7280;
    --nd-accent: #ef4444;
    --nd-accent2: #dc2626;
    --nd-amber: #f59e0b;
    --nd-green: #34d399;
    --nd-cyan: #22d3ee;
    --nd-purple: #a78bfa;
    --nd-red: #ef4444;
    --nd-blue: #60a5fa;
}
.nd-bg { background: var(--nd-bg); min-height: 100vh; padding-bottom: 3rem; }
.nd-wrap { max-width: 1400px; margin: 0 auto; padding: 2rem 1.5rem; }
.nd-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
.nd-title { font-size: 1.8rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 0.75rem; }
.nd-title .nd-icon { font-size: 1.5rem; color: var(--nd-accent); }
.nd-subtitle { color: var(--nd-muted); font-size: 0.85rem; margin-top: 0.25rem; }

/* DEFCON banner */
.nd-defcon-banner { padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.defcon-5 { background: linear-gradient(135deg, rgba(34,211,238,0.1), rgba(34,211,238,0.05)); border: 1px solid rgba(34,211,238,0.3); }
.defcon-4 { background: linear-gradient(135deg, rgba(52,211,153,0.1), rgba(52,211,153,0.05)); border: 1px solid rgba(52,211,153,0.3); }
.defcon-3 { background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(245,158,11,0.05)); border: 1px solid rgba(245,158,11,0.3); }
.defcon-2 { background: linear-gradient(135deg, rgba(239,68,68,0.1), rgba(239,68,68,0.05)); border: 1px solid rgba(239,68,68,0.3); }
.defcon-1 { background: linear-gradient(135deg, rgba(239,68,68,0.25), rgba(220,38,38,0.15)); border: 2px solid rgba(239,68,68,0.6); animation: defcon-pulse 1.5s ease-in-out infinite; }
@keyframes defcon-pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.85; } }
.nd-defcon-level { font-size: 2rem; font-weight: 900; letter-spacing: 2px; }
.nd-defcon-label { font-size: 0.85rem; color: var(--nd-muted); text-transform: uppercase; letter-spacing: 1px; }
.nd-defcon-posture { font-size: 0.9rem; font-weight: 600; text-transform: uppercase; }

/* Flash message */
.nd-flash { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
.nd-flash-success { background: rgba(34,211,153,0.1); border: 1px solid rgba(34,211,153,0.3); color: var(--nd-green); }
.nd-flash-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: var(--nd-red); }

/* Tabs */
.nd-tabs { display: flex; gap: 0.25rem; margin-bottom: 2rem; flex-wrap: wrap; border-bottom: 1px solid var(--nd-border); padding-bottom: 0; }
.nd-tab { padding: 0.6rem 1rem; color: var(--nd-muted); text-decoration: none; font-size: 0.85rem; font-weight: 500; border-bottom: 2px solid transparent; transition: all 0.2s; }
.nd-tab:hover { color: #fff; }
.nd-tab.active { color: var(--nd-accent); border-bottom-color: var(--nd-accent); }

/* Stats grid */
.nd-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.nd-stat { background: var(--nd-surface); border: 1px solid var(--nd-border); border-radius: 10px; padding: 1rem 1.25rem; }
.nd-stat-label { font-size: 0.75rem; color: var(--nd-dim); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem; }
.nd-stat-value { font-size: 1.5rem; font-weight: 700; color: #fff; }
.nd-stat-sub { font-size: 0.75rem; color: var(--nd-muted); margin-top: 0.15rem; }

/* Cards */
.nd-card { background: var(--nd-surface); border: 1px solid var(--nd-border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; }
.nd-card-title { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
.nd-card-title i { color: var(--nd-accent); }

/* Table */
.nd-table-wrap { overflow-x: auto; }
.nd-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.nd-table th { text-align: left; padding: 0.6rem 0.75rem; color: var(--nd-muted); font-weight: 600; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.5px; border-bottom: 1px solid var(--nd-border); }
.nd-table td { padding: 0.6rem 0.75rem; color: var(--nd-text); border-bottom: 1px solid rgba(255,255,255,0.03); vertical-align: middle; }
.nd-table tr:hover td { background: rgba(255,255,255,0.02); }

/* Badges */
.nd-badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
.nd-badge-standby { background: rgba(34,211,238,0.12); color: #22d3ee; }
.nd-badge-armed { background: rgba(245,158,11,0.12); color: #f59e0b; }
.nd-badge-launched { background: rgba(239,68,68,0.12); color: #ef4444; }
.nd-badge-decom { background: rgba(107,114,128,0.2); color: #9ca3af; }
.nd-badge-maint { background: rgba(167,139,250,0.12); color: #a78bfa; }
.nd-badge-passed { background: rgba(34,211,153,0.12); color: #34d399; }
.nd-badge-failed { background: rgba(239,68,68,0.12); color: #ef4444; }
.nd-badge-scheduled { background: rgba(96,165,250,0.12); color: #60a5fa; }
.nd-badge-success { background: rgba(34,211,153,0.12); color: #34d399; }
.nd-badge-partial { background: rgba(245,158,11,0.12); color: #f59e0b; }
.nd-badge-intercepted { background: rgba(167,139,250,0.12); color: #a78bfa; }
.nd-badge-aborted { background: rgba(107,114,128,0.2); color: #9ca3af; }
.nd-badge-failure { background: rgba(239,68,68,0.12); color: #ef4444; }

/* Buttons */
.nd-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
.nd-btn-red { background: var(--nd-accent); color: #fff; }
.nd-btn-red:hover { background: var(--nd-accent2); transform: translateY(-1px); }
.nd-btn-outline { background: transparent; color: var(--nd-muted); border: 1px solid var(--nd-border); }
.nd-btn-outline:hover { border-color: var(--nd-accent); color: #fff; }
.nd-btn-sm { padding: 0.3rem 0.7rem; font-size: 0.75rem; }
.nd-btn-amber { background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
.nd-btn-amber:hover { background: rgba(245,158,11,0.25); }
.nd-btn-green { background: rgba(34,211,153,0.15); color: #34d399; border: 1px solid rgba(34,211,153,0.3); }
.nd-btn-green:hover { background: rgba(34,211,153,0.25); }
.nd-btn-cyan { background: rgba(34,211,238,0.15); color: #22d3ee; border: 1px solid rgba(34,211,238,0.3); }
.nd-btn-cyan:hover { background: rgba(34,211,238,0.25); }

/* Forms */
.nd-input, .nd-select, .nd-textarea { width: 100%; padding: 0.5rem 0.75rem; background: rgba(0,0,0,0.3); border: 1px solid var(--nd-border); border-radius: 6px; color: #fff; font-size: 0.85rem; }
.nd-input:focus, .nd-select:focus, .nd-textarea:focus { outline: none; border-color: var(--nd-accent); }
.nd-textarea { min-height: 80px; resize: vertical; }
.nd-form-row { margin-bottom: 0.75rem; }
.nd-label { display: block; font-size: 0.75rem; color: var(--nd-muted); text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 0.3rem; }
.nd-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; }

/* Modals */
.nd-modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; padding: 1rem; }
.nd-modal-bg.open { display: flex; }
.nd-modal { background: #111118; border: 1px solid var(--nd-border); border-radius: 16px; padding: 1.75rem; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto; }
.nd-modal h3 { font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
.nd-modal h3 i { color: var(--nd-accent); }

/* Grid layouts */
.nd-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.nd-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }

/* Yield bar */
.nd-yield-bar { height: 6px; background: rgba(255,255,255,0.05); border-radius: 3px; overflow: hidden; margin-top: 0.3rem; }
.nd-yield-fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg, var(--nd-amber), var(--nd-accent)); }

/* Silo visual */
.nd-silo-visual { display: flex; gap: 3px; margin-top: 0.4rem; }
.nd-silo-slot { width: 14px; height: 14px; border-radius: 3px; border: 1px solid var(--nd-border); }
.nd-silo-slot.filled { background: var(--nd-accent); border-color: var(--nd-accent); }
.nd-silo-slot.empty { background: rgba(255,255,255,0.03); }

/* Action bar */
.nd-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }

/* Responsive */
@media (max-width: 768px) {
    .nd-grid-2, .nd-grid-3 { grid-template-columns: 1fr; }
    .nd-stats { grid-template-columns: repeat(2, 1fr); }
    .nd-header { flex-direction: column; align-items: flex-start; }
    .nd-defcon-banner { flex-direction: column; text-align: center; }
}
</style>

<div class="nd-bg">
<div class="nd-wrap">

<!-- Header -->
<div class="nd-header">
    <div>
        <div class="nd-title"><i class="fas fa-radiation nd-icon"></i> Nuclear Deterrence System</div>
        <div class="nd-subtitle">Level 5 · Supreme Command · The Living Military</div>
    </div>
    <?php if ($isCommander): ?>
    <div class="nd-actions">
        <button class="nd-btn nd-btn-red" onclick="document.getElementById('warheadModal').classList.add('open')"><i class="fas fa-bomb"></i> Register Warhead</button>
        <button class="nd-btn nd-btn-amber" onclick="document.getElementById('siloModal').classList.add('open')"><i class="fas fa-warehouse"></i> Create Silo</button>
        <button class="nd-btn nd-btn-outline" onclick="document.getElementById('defconModal').classList.add('open')"><i class="fas fa-shield-halved"></i> Change DEFCON</button>
    </div>
    <?php endif; ?>
</div>

<!-- Flash Message -->
<?php if ($msg): ?>
<div class="nd-flash nd-flash-<?= $msgType ?>"><?= $msg ?></div>
<?php endif; ?>

<!-- DEFCON Banner -->
<?php
$defconColors = [1 => '#ef4444', 2 => '#f97316', 3 => '#f59e0b', 4 => '#34d399', 5 => '#22d3ee'];
$defconLabels = [1 => 'NUCLEAR WAR IMMINENT', 2 => 'NEXT STEP TO NUCLEAR WAR', 3 => 'INCREASE IN FORCE READINESS', 4 => 'INCREASED INTELLIGENCE & SECURITY', 5 => 'LOWEST STATE OF READINESS'];
$postureLabels = ['peacetime' => 'Peacetime Operations', 'elevated' => 'Elevated Alert', 'war_ready' => 'War Ready', 'launch_ready' => 'Launch Ready', 'maximum' => 'Maximum Force Posture'];
?>
<div class="nd-defcon-banner defcon-<?= $currentDefcon ?>">
    <div>
        <div class="nd-defcon-label">Current Defense Condition</div>
        <div class="nd-defcon-level" style="color:<?= $defconColors[$currentDefcon] ?? '#fff' ?>">DEFCON <?= $currentDefcon ?></div>
    </div>
    <div style="text-align:right">
        <div class="nd-defcon-label">Response Posture</div>
        <div class="nd-defcon-posture" style="color:<?= $defconColors[$currentDefcon] ?? '#fff' ?>"><?= $postureLabels[$currentPosture] ?? ucfirst($currentPosture) ?></div>
    </div>
    <div style="text-align:right">
        <div class="nd-defcon-label">Status</div>
        <div style="font-size:0.85rem;color:var(--nd-text)"><?= $defconLabels[$currentDefcon] ?? '' ?></div>
    </div>
</div>

<!-- Tabs -->
<div class="nd-tabs">
    <?php foreach (['overview' => 'Overview', 'arsenal' => 'Arsenal', 'silos' => 'Silos', 'defcon' => 'DEFCON', 'launches' => 'Launches', 'inspections' => 'Inspections', 'simulations' => 'Simulations'] as $tKey => $tLabel): ?>
        <a href="?tab=<?= $tKey ?>" class="nd-tab <?= $tab === $tKey ? 'active' : '' ?>"><?= $tLabel ?></a>
    <?php endforeach; ?>
</div>

<?php if ($tab === 'overview'): ?>
<!-- ══════════════ OVERVIEW TAB ══════════════ -->
<div class="nd-stats">
    <div class="nd-stat">
        <div class="nd-stat-label">Total Warheads</div>
        <div class="nd-stat-value"><?= number_format($arsenalStats['total'] ?? 0) ?></div>
        <div class="nd-stat-sub"><?= $arsenalStats['standby'] ?? 0 ?> standby · <?= $arsenalStats['armed'] ?? 0 ?> armed</div>
    </div>
    <div class="nd-stat">
        <div class="nd-stat-label">Total Yield</div>
        <div class="nd-stat-value"><?= number_format($arsenalStats['total_yield'] ?? 0, 1) ?> MT</div>
        <div class="nd-stat-sub">Avg <?= number_format($arsenalStats['avg_yield'] ?? 0, 2) ?> MT · Max <?= number_format($arsenalStats['max_yield'] ?? 0, 1) ?> MT</div>
    </div>
    <div class="nd-stat">
        <div class="nd-stat-label">Silos</div>
        <div class="nd-stat-value"><?= $siloStats['total'] ?? 0 ?></div>
        <div class="nd-stat-sub"><?= $siloStats['total_occ'] ?? 0 ?>/<?= $siloStats['total_cap'] ?? 0 ?> occupied · <?= $siloStats['ready'] ?? 0 ?> ready</div>
    </div>
    <div class="nd-stat">
        <div class="nd-stat-label">Max Range</div>
        <div class="nd-stat-value"><?= number_format($arsenalStats['max_range'] ?? 0) ?> km</div>
        <div class="nd-stat-sub">Global reach capability</div>
    </div>
    <div class="nd-stat">
        <div class="nd-stat-label">Launched</div>
        <div class="nd-stat-value"><?= $arsenalStats['launched'] ?? 0 ?></div>
        <div class="nd-stat-sub"><?= count($launches) ?> entries in log</div>
    </div>
    <div class="nd-stat">
        <div class="nd-stat-label">Decommissioned</div>
        <div class="nd-stat-value"><?= $arsenalStats['decom'] ?? 0 ?></div>
        <div class="nd-stat-sub"><?= $arsenalStats['maint'] ?? 0 ?> in maintenance</div>
    </div>
    <div class="nd-stat">
        <div class="nd-stat-label">Simulations</div>
        <div class="nd-stat-value"><?= $simStats['total'] ?? 0 ?></div>
        <div class="nd-stat-sub"><?= $simStats['successes'] ?? 0 ?> success · <?= $simStats['failures'] ?? 0 ?> failed</div>
    </div>
    <div class="nd-stat">
        <div class="nd-stat-label">Overdue Inspections</div>
        <div class="nd-stat-value" style="color:<?= ($overdueInsp['cnt'] ?? 0) > 0 ? 'var(--nd-red)' : 'var(--nd-green)' ?>"><?= $overdueInsp['cnt'] ?? 0 ?></div>
        <div class="nd-stat-sub"><?= count($inspections) ?> total inspections</div>
    </div>
</div>

<!-- Quick View: Recent Activity -->
<div class="nd-grid-2">
    <div class="nd-card">
        <div class="nd-card-title"><i class="fas fa-clock-rotate-left"></i> Recent Launches</div>
        <?php if (empty($launches)): ?>
            <div style="color:var(--nd-dim);font-size:0.85rem;">No launches recorded.</div>
        <?php else: ?>
            <?php foreach (array_slice($launches, 0, 5) as $l): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.4rem 0;border-bottom:1px solid var(--nd-border);">
                <div>
                    <span style="color:#fff;font-weight:600;"><?= htmlspecialchars($l['designation'] ?? 'Unknown') ?></span>
                    <span style="color:var(--nd-muted);font-size:0.8rem;margin-left:0.5rem;">→ <?= htmlspecialchars($l['target_zone'] ?? '') ?></span>
                </div>
                <span class="nd-badge nd-badge-<?= $l['result'] ?? 'launched' ?>"><?= $l['result'] ?? 'unknown' ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="nd-card">
        <div class="nd-card-title"><i class="fas fa-shield-halved"></i> DEFCON History</div>
        <?php if (empty($defconHistory)): ?>
            <div style="color:var(--nd-dim);font-size:0.85rem;">No DEFCON changes recorded. Default: DEFCON 5.</div>
        <?php else: ?>
            <?php foreach (array_slice($defconHistory, 0, 5) as $dh): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.4rem 0;border-bottom:1px solid var(--nd-border);">
                <div>
                    <span style="color:<?= $defconColors[$dh['new_level']] ?? '#fff' ?>;font-weight:700;">DEFCON <?= $dh['new_level'] ?></span>
                    <span style="color:var(--nd-muted);font-size:0.8rem;margin-left:0.5rem;"><?= htmlspecialchars(mb_strimwidth($dh['reason'], 0, 40, '...')) ?></span>
                </div>
                <span style="color:var(--nd-dim);font-size:0.75rem;"><?= date('M j', strtotime($dh['effective_at'])) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($tab === 'arsenal'): ?>
<!-- ══════════════ ARSENAL TAB ══════════════ -->
<?php if ($isFlag): ?>
<div class="nd-actions">
    <?php if ($isCommander): ?>
    <button class="nd-btn nd-btn-red" onclick="document.getElementById('warheadModal').classList.add('open')"><i class="fas fa-bomb"></i> Register Warhead</button>
    <?php endif; ?>
    <button class="nd-btn nd-btn-cyan" onclick="document.getElementById('inspectModal').classList.add('open')"><i class="fas fa-clipboard-check"></i> Schedule Inspection</button>
</div>
<?php endif; ?>

<div class="nd-card">
    <div class="nd-card-title"><i class="fas fa-bomb"></i> Warhead Inventory (<?= count($warheads) ?>)</div>
    <div class="nd-table-wrap">
    <table class="nd-table">
        <thead><tr>
            <th>Designation</th>
            <th>Name</th>
            <th>Type</th>
            <th>Yield (MT)</th>
            <th>Range (km)</th>
            <th>Silo</th>
            <th>Auth Level</th>
            <th>Status</th>
            <th>Last Inspected</th>
            <?php if ($isFlag): ?><th>Actions</th><?php endif; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($warheads as $w):
            $statusClass = match($w['status']) {
                'standby' => 'standby', 'armed' => 'armed', 'launched' => 'launched',
                'decommissioned' => 'decom', 'maintenance' => 'maint', default => 'standby'
            };
            $yieldPct = $arsenalStats['max_yield'] > 0 ? min(100, ($w['yield_megatons'] / $arsenalStats['max_yield']) * 100) : 0;
        ?>
        <tr>
            <td><strong style="color:#fff"><?= htmlspecialchars($w['designation']) ?></strong></td>
            <td><?= htmlspecialchars($w['weapon_name'] ?? '—') ?></td>
            <td><span style="color:var(--nd-cyan);font-size:0.8rem;text-transform:uppercase;"><?= htmlspecialchars($w['warhead_type']) ?></span></td>
            <td>
                <?= number_format($w['yield_megatons'], 2) ?>
                <div class="nd-yield-bar"><div class="nd-yield-fill" style="width:<?= $yieldPct ?>%"></div></div>
            </td>
            <td><?= number_format($w['range_km']) ?></td>
            <td style="font-size:0.8rem;"><?= htmlspecialchars($w['silo_location'] ?: '—') ?></td>
            <td style="text-align:center;"><span style="color:<?= $w['authorization_level'] >= 11 ? 'var(--nd-red)' : 'var(--nd-amber)' ?>;font-weight:700;"><?= $w['authorization_level'] ?></span></td>
            <td><span class="nd-badge nd-badge-<?= $statusClass ?>"><?= $w['status'] ?></span></td>
            <td style="font-size:0.8rem;color:var(--nd-dim);"><?= $w['last_inspection'] ? date('M j, Y', strtotime($w['last_inspection'])) : 'Never' ?></td>
            <?php if ($isFlag): ?>
            <td>
                <?php if ($w['status'] !== 'launched' && $w['status'] !== 'decommissioned'): ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_nuclear'] ?>">
                    <input type="hidden" name="action" value="update_warhead_status">
                    <input type="hidden" name="wh_id" value="<?= $w['id'] ?>">
                    <select name="new_status" class="nd-select" style="width:auto;padding:0.2rem 0.4rem;font-size:0.75rem;" onchange="this.form.submit()">
                        <option value="">Change...</option>
                        <option value="standby">Standby</option>
                        <option value="armed">Armed</option>
                        <option value="maintenance">Maintenance</option>
                        <?php if ($isCommander): ?><option value="decommissioned">Decommission</option><?php endif; ?>
                    </select>
                </form>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($warheads)): ?><tr><td colspan="10" style="color:var(--nd-dim);text-align:center;padding:2rem;">No warheads registered. The Commander must register the first warhead.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php elseif ($tab === 'silos'): ?>
<!-- ══════════════ SILOS TAB ══════════════ -->
<?php if ($isCommander): ?>
<div class="nd-actions">
    <button class="nd-btn nd-btn-amber" onclick="document.getElementById('siloModal').classList.add('open')"><i class="fas fa-warehouse"></i> Create Silo</button>
</div>
<?php endif; ?>

<div class="nd-grid-3">
<?php foreach ($silos as $s):
    $capPct = $s['capacity'] > 0 ? round(($s['occupied'] / $s['capacity']) * 100) : 0;
    $statusColor = match($s['status']) {
        'standby' => 'var(--nd-cyan)', 'active' => 'var(--nd-green)', 'compromised' => 'var(--nd-red)',
        'destroyed' => 'var(--nd-dim)', default => 'var(--nd-muted)'
    };
?>
<div class="nd-card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.75rem;">
        <div>
            <div style="font-size:0.7rem;color:var(--nd-dim);text-transform:uppercase;letter-spacing:1px;"><?= htmlspecialchars($s['silo_id']) ?></div>
            <div style="font-size:1rem;font-weight:700;color:#fff;"><?= htmlspecialchars($s['silo_name']) ?></div>
        </div>
        <span class="nd-badge nd-badge-<?= $s['status'] === 'standby' ? 'standby' : ($s['status'] === 'active' ? 'passed' : 'failed') ?>"><?= $s['status'] ?></span>
    </div>
    <div style="font-size:0.8rem;color:var(--nd-muted);margin-bottom:0.5rem;"><i class="fas fa-map-marker-alt" style="color:var(--nd-dim);margin-right:0.3rem;"></i><?= htmlspecialchars($s['location'] ?: 'Classified') ?></div>
    <div style="font-size:0.8rem;color:var(--nd-muted);margin-bottom:0.5rem;"><i class="fas fa-shield-halved" style="color:var(--nd-dim);margin-right:0.3rem;"></i>Hardening: <span style="color:var(--nd-amber)"><?= ucfirst(str_replace('_', ' ', $s['hardening_level'])) ?></span></div>
    <div style="font-size:0.8rem;color:var(--nd-muted);">Capacity: <span style="color:#fff;font-weight:600;"><?= $s['occupied'] ?>/<?= $s['capacity'] ?></span> (<?= $capPct ?>%)</div>
    <div class="nd-silo-visual">
        <?php for ($i = 0; $i < $s['capacity']; $i++): ?>
            <div class="nd-silo-slot <?= $i < $s['occupied'] ? 'filled' : 'empty' ?>"></div>
        <?php endfor; ?>
    </div>
</div>
<?php endforeach; ?>
<?php if (empty($silos)): ?>
<div class="nd-card" style="grid-column:1/-1;text-align:center;color:var(--nd-dim);padding:3rem;">No silos constructed. The Commander must create the first silo.</div>
<?php endif; ?>
</div>

<?php elseif ($tab === 'defcon'): ?>
<!-- ══════════════ DEFCON TAB ══════════════ -->
<?php if ($isCommander): ?>
<div class="nd-actions">
    <button class="nd-btn nd-btn-outline" onclick="document.getElementById('defconModal').classList.add('open')"><i class="fas fa-shield-halved"></i> Change DEFCON Level</button>
</div>
<?php endif; ?>

<!-- DEFCON Scale Visual -->
<div class="nd-card">
    <div class="nd-card-title"><i class="fas fa-gauge-high"></i> Defense Readiness Condition Scale</div>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        <?php for ($d = 5; $d >= 1; $d--): ?>
        <div style="flex:1;min-width:150px;padding:1rem;border-radius:10px;text-align:center;background:<?= $d === $currentDefcon ? 'rgba(255,255,255,0.08)' : 'var(--nd-surface)' ?>;border:2px solid <?= $d === $currentDefcon ? ($defconColors[$d] ?? '#fff') : 'var(--nd-border)' ?>;">
            <div style="font-size:1.8rem;font-weight:900;color:<?= $defconColors[$d] ?>"><?= $d ?></div>
            <div style="font-size:0.7rem;color:var(--nd-muted);margin-top:0.3rem;"><?= $defconLabels[$d] ?></div>
            <?php if ($d === $currentDefcon): ?><div style="font-size:0.65rem;color:<?= $defconColors[$d] ?>;margin-top:0.3rem;font-weight:700;">◄ CURRENT</div><?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>
</div>

<!-- DEFCON History -->
<div class="nd-card">
    <div class="nd-card-title"><i class="fas fa-clock-rotate-left"></i> DEFCON Change Log</div>
    <div class="nd-table-wrap">
    <table class="nd-table">
        <thead><tr><th>Time</th><th>From</th><th>To</th><th>Posture</th><th>Reason</th><th>Reverted</th></tr></thead>
        <tbody>
        <?php foreach ($defconHistory as $dh): ?>
        <tr>
            <td style="font-size:0.8rem;"><?= date('M j Y H:i', strtotime($dh['effective_at'])) ?></td>
            <td><span style="color:<?= $defconColors[$dh['previous_level']] ?? '#fff' ?>;font-weight:700;">DC-<?= $dh['previous_level'] ?></span></td>
            <td><span style="color:<?= $defconColors[$dh['new_level']] ?? '#fff' ?>;font-weight:700;">DC-<?= $dh['new_level'] ?></span></td>
            <td style="font-size:0.8rem;"><?= ucfirst(str_replace('_', ' ', $dh['response_posture'])) ?></td>
            <td style="font-size:0.8rem;max-width:300px;"><?= htmlspecialchars(mb_strimwidth($dh['reason'], 0, 80, '...')) ?></td>
            <td style="font-size:0.8rem;color:var(--nd-dim);"><?= $dh['reverted_at'] ? date('M j H:i', strtotime($dh['reverted_at'])) : '<span style="color:var(--nd-green)">Active</span>' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($defconHistory)): ?><tr><td colspan="6" style="text-align:center;color:var(--nd-dim);">No DEFCON changes recorded.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php elseif ($tab === 'launches'): ?>
<!-- ══════════════ LAUNCHES TAB ══════════════ -->
<?php if ($isCommander && $currentDefcon <= 2): ?>
<div class="nd-actions">
    <button class="nd-btn nd-btn-red" onclick="document.getElementById('launchModal').classList.add('open')"><i class="fas fa-rocket"></i> Authorize Launch</button>
</div>
<?php elseif ($isCommander): ?>
<div style="color:var(--nd-amber);font-size:0.85rem;margin-bottom:1rem;"><i class="fas fa-triangle-exclamation"></i> Launch authorization requires DEFCON 2 or lower. Current: DEFCON <?= $currentDefcon ?>.</div>
<?php endif; ?>

<div class="nd-card">
    <div class="nd-card-title"><i class="fas fa-rocket"></i> Launch History</div>
    <div class="nd-table-wrap">
    <table class="nd-table">
        <thead><tr><th>ID</th><th>Warhead</th><th>Yield</th><th>Target Zone</th><th>Launch Time</th><th>Impact Time</th><th>Result</th><th>Damage</th></tr></thead>
        <tbody>
        <?php foreach ($launches as $l): ?>
        <tr>
            <td style="color:var(--nd-dim);">#<?= $l['id'] ?></td>
            <td><strong style="color:#fff;"><?= htmlspecialchars($l['designation'] ?? 'WH-' . $l['warhead_id']) ?></strong></td>
            <td><?= isset($l['yield_megatons']) ? number_format($l['yield_megatons'], 2) . ' MT' : '—' ?></td>
            <td style="color:var(--nd-amber);"><?= htmlspecialchars($l['target_zone'] ?? '—') ?></td>
            <td style="font-size:0.8rem;"><?= date('M j Y H:i:s', strtotime($l['launch_time'])) ?></td>
            <td style="font-size:0.8rem;"><?= $l['impact_time'] ? date('M j H:i:s', strtotime($l['impact_time'])) : 'Pending' ?></td>
            <td>
                <?php
                $rClass = match($l['result']) { 'hit' => 'launched', 'miss' => 'maint', 'intercepted' => 'intercepted', 'aborted' => 'aborted', default => 'standby' };
                ?>
                <span class="nd-badge nd-badge-<?= $rClass ?>"><?= $l['result'] ?? 'unknown' ?></span>
            </td>
            <td style="color:var(--nd-red);font-weight:600;"><?= number_format($l['damage_dealt'] ?? 0) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($launches)): ?><tr><td colspan="8" style="text-align:center;color:var(--nd-dim);padding:2rem;">No launches in the log. May it stay this way.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php elseif ($tab === 'inspections'): ?>
<!-- ══════════════ INSPECTIONS TAB ══════════════ -->
<?php if ($isFlag): ?>
<div class="nd-actions">
    <button class="nd-btn nd-btn-cyan" onclick="document.getElementById('inspectModal').classList.add('open')"><i class="fas fa-clipboard-check"></i> Schedule Inspection</button>
</div>
<?php endif; ?>

<?php if (($overdueInsp['cnt'] ?? 0) > 0): ?>
<div class="nd-flash nd-flash-error"><i class="fas fa-triangle-exclamation"></i> <?= $overdueInsp['cnt'] ?> inspection(s) are overdue!</div>
<?php endif; ?>

<div class="nd-card">
    <div class="nd-card-title"><i class="fas fa-clipboard-check"></i> Inspection Records</div>
    <div class="nd-table-wrap">
    <table class="nd-table">
        <thead><tr><th>ID</th><th>Target</th><th>Type</th><th>Scheduled</th><th>Status</th><th>Risk</th><th>Findings</th><th>Completed</th><th>Next Due</th><?php if ($isFlag): ?><th>Actions</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($inspections as $ins):
            $iClass = match($ins['status']) {
                'passed' => 'passed', 'failed' => 'failed', 'scheduled' => 'scheduled',
                'in_progress' => 'armed', 'deferred' => 'maint', default => 'standby'
            };
            $overdue = in_array($ins['status'], ['scheduled','in_progress']) && strtotime($ins['scheduled_date']) < time();
        ?>
        <tr style="<?= $overdue ? 'background:rgba(239,68,68,0.05);' : '' ?>">
            <td style="color:var(--nd-dim);">#<?= $ins['id'] ?></td>
            <td><strong style="color:#fff;"><?= htmlspecialchars($ins['target_label'] ?? '—') ?></strong><br><span style="font-size:0.7rem;color:var(--nd-dim);"><?= ucfirst($ins['target_type']) ?></span></td>
            <td style="font-size:0.8rem;"><?= ucfirst(str_replace('_', ' ', $ins['inspection_type'])) ?></td>
            <td style="font-size:0.8rem;<?= $overdue ? 'color:var(--nd-red);font-weight:600;' : '' ?>"><?= date('M j, Y', strtotime($ins['scheduled_date'])) ?></td>
            <td><span class="nd-badge nd-badge-<?= $iClass ?>"><?= str_replace('_', ' ', $ins['status']) ?></span></td>
            <td>
                <?php if ($ins['risk_level'] !== 'none'): ?>
                <span style="color:<?= match($ins['risk_level']) { 'critical' => 'var(--nd-red)', 'high' => '#f97316', 'moderate' => 'var(--nd-amber)', default => 'var(--nd-muted)' } ?>;font-size:0.8rem;font-weight:600;"><?= ucfirst($ins['risk_level']) ?></span>
                <?php else: ?>
                <span style="color:var(--nd-dim);font-size:0.8rem;">—</span>
                <?php endif; ?>
            </td>
            <td style="font-size:0.8rem;max-width:200px;"><?= htmlspecialchars(mb_strimwidth($ins['findings'] ?? '', 0, 60, '...')) ?: '<span style="color:var(--nd-dim)">—</span>' ?></td>
            <td style="font-size:0.8rem;color:var(--nd-dim);"><?= $ins['completed_date'] ? date('M j, Y', strtotime($ins['completed_date'])) : '—' ?></td>
            <td style="font-size:0.8rem;color:var(--nd-dim);"><?= $ins['next_due'] ? date('M j, Y', strtotime($ins['next_due'])) : '—' ?></td>
            <?php if ($isFlag): ?>
            <td>
                <?php if (in_array($ins['status'], ['scheduled','in_progress'])): ?>
                <button class="nd-btn nd-btn-sm nd-btn-green" onclick="openCompleteInspection(<?= $ins['id'] ?>)"><i class="fas fa-check"></i></button>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($inspections)): ?><tr><td colspan="10" style="text-align:center;color:var(--nd-dim);padding:2rem;">No inspections recorded.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php elseif ($tab === 'simulations'): ?>
<!-- ══════════════ SIMULATIONS TAB ══════════════ -->
<?php if ($isFlag): ?>
<div class="nd-actions">
    <button class="nd-btn nd-btn-amber" onclick="document.getElementById('simModal').classList.add('open')"><i class="fas fa-crosshairs"></i> Run Strike Simulation</button>
</div>
<?php endif; ?>

<!-- Sim stats -->
<div class="nd-stats">
    <div class="nd-stat"><div class="nd-stat-label">Total Simulations</div><div class="nd-stat-value"><?= $simStats['total'] ?? 0 ?></div></div>
    <div class="nd-stat"><div class="nd-stat-label">Successes</div><div class="nd-stat-value" style="color:var(--nd-green);"><?= $simStats['successes'] ?? 0 ?></div></div>
    <div class="nd-stat"><div class="nd-stat-label">Failures</div><div class="nd-stat-value" style="color:var(--nd-red);"><?= $simStats['failures'] ?? 0 ?></div></div>
    <div class="nd-stat"><div class="nd-stat-label">Intercepted</div><div class="nd-stat-value" style="color:var(--nd-purple);"><?= $simStats['intercepted'] ?? 0 ?></div></div>
    <div class="nd-stat"><div class="nd-stat-label">Warheads Simulated</div><div class="nd-stat-value"><?= $simStats['total_wh_used'] ?? 0 ?></div></div>
    <div class="nd-stat"><div class="nd-stat-label">Avg Yield</div><div class="nd-stat-value"><?= number_format($simStats['avg_yield'] ?? 0, 2) ?> MT</div></div>
</div>

<div class="nd-card">
    <div class="nd-card-title"><i class="fas fa-crosshairs"></i> Strike Simulation Records</div>
    <div class="nd-table-wrap">
    <table class="nd-table">
        <thead><tr><th>Codename</th><th>Scenario</th><th>Warheads</th><th>Est. Yield</th><th>Est. Casualties</th><th>Collateral (km)</th><th>Duration</th><th>Outcome</th><th>Date</th><?php if ($isFlag): ?><th>Actions</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($simulations as $sim):
            $oClass = match($sim['outcome']) {
                'success' => 'success', 'partial' => 'partial', 'failure' => 'failure',
                'intercepted' => 'intercepted', 'aborted' => 'aborted', default => 'scheduled'
            };
        ?>
        <tr>
            <td><strong style="color:#fff;"><?= htmlspecialchars($sim['sim_codename']) ?></strong><br><span style="font-size:0.75rem;color:var(--nd-dim);"><?= htmlspecialchars($sim['sim_name']) ?></span></td>
            <td style="font-size:0.8rem;color:var(--nd-cyan);"><?= ucfirst(str_replace('_', ' ', $sim['scenario_type'])) ?></td>
            <td style="text-align:center;font-weight:600;"><?= $sim['warheads_used'] ?></td>
            <td><?= number_format($sim['estimated_yield_mt'], 2) ?> MT</td>
            <td style="color:var(--nd-red);"><?= number_format($sim['estimated_casualties']) ?></td>
            <td><?= number_format($sim['collateral_radius_km'], 1) ?></td>
            <td style="font-size:0.8rem;"><?= $sim['duration_minutes'] ?> min</td>
            <td>
                <?php if ($sim['outcome']): ?>
                    <span class="nd-badge nd-badge-<?= $oClass ?>"><?= $sim['outcome'] ?></span>
                <?php else: ?>
                    <span class="nd-badge nd-badge-scheduled">In Progress</span>
                <?php endif; ?>
            </td>
            <td style="font-size:0.8rem;"><?= date('M j, Y', strtotime($sim['sim_date'])) ?></td>
            <?php if ($isFlag): ?>
            <td>
                <?php if (!$sim['outcome']): ?>
                <button class="nd-btn nd-btn-sm nd-btn-green" onclick="openCompleteSim(<?= $sim['id'] ?>)"><i class="fas fa-flag-checkered"></i></button>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($simulations)): ?><tr><td colspan="10" style="text-align:center;color:var(--nd-dim);padding:2rem;">No simulations conducted yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

</div><!-- .nd-wrap -->
</div><!-- .nd-bg -->

<!-- ═══════════════════════════════════ MODALS ═══════════════════════════════════ -->

<!-- Register Warhead Modal -->
<div class="nd-modal-bg" id="warheadModal">
<div class="nd-modal">
    <h3><i class="fas fa-bomb"></i> Register New Warhead</h3>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_nuclear'] ?>">
        <input type="hidden" name="action" value="register_warhead">
        <div class="nd-form-grid">
            <div class="nd-form-row"><label class="nd-label">Weapon Code *</label><input name="weapon_code" class="nd-input" required maxlength="64" placeholder="e.g. TRIDENT-VII"></div>
            <div class="nd-form-row"><label class="nd-label">Weapon Name *</label><input name="weapon_name" class="nd-input" required maxlength="128" placeholder="e.g. Trident VII MIRV"></div>
        </div>
        <div class="nd-form-grid">
            <div class="nd-form-row"><label class="nd-label">Weapon Platform</label>
                <select name="weapon_type" class="nd-select">
                    <option value="icbm">ICBM</option><option value="slbm">SLBM</option><option value="tactical">Tactical</option>
                    <option value="orbital">Orbital</option><option value="emp">EMP</option><option value="cyber_nuke">Cyber Nuke</option>
                    <option value="quantum_strike">Quantum Strike</option>
                </select>
            </div>
            <div class="nd-form-row"><label class="nd-label">Warhead Type</label>
                <select name="warhead_type" class="nd-select">
                    <option value="strategic">Strategic</option><option value="tactical">Tactical</option>
                    <option value="quantum">Quantum</option><option value="emp">EMP</option><option value="antimatter">Antimatter</option>
                </select>
            </div>
        </div>
        <div class="nd-form-grid">
            <div class="nd-form-row"><label class="nd-label">Yield (Megatons)</label><input name="yield_mt" class="nd-input" type="number" step="0.01" min="0.01" max="9999.99" value="1.00"></div>
            <div class="nd-form-row"><label class="nd-label">Range (km)</label><input name="range_km" class="nd-input" type="number" min="1" max="999999" value="5000"></div>
        </div>
        <div class="nd-form-grid">
            <div class="nd-form-row"><label class="nd-label">Assign to Silo</label>
                <select name="silo_id" class="nd-select">
                    <option value="0">Unassigned</option>
                    <?php foreach ($silos as $s): ?>
                        <?php if ($s['occupied'] < $s['capacity']): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['silo_id'] . ' — ' . $s['silo_name']) ?> (<?= $s['occupied'] ?>/<?= $s['capacity'] ?>)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="nd-form-row"><label class="nd-label">Authorization Level (9-11)</label><input name="auth_level" class="nd-input" type="number" min="9" max="11" value="11"></div>
        </div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
            <button type="button" class="nd-btn nd-btn-outline" onclick="this.closest('.nd-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="nd-btn nd-btn-red"><i class="fas fa-bomb"></i> Register Warhead</button>
        </div>
    </form>
</div>
</div>

<!-- Create Silo Modal -->
<div class="nd-modal-bg" id="siloModal">
<div class="nd-modal">
    <h3><i class="fas fa-warehouse"></i> Construct New Silo</h3>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_nuclear'] ?>">
        <input type="hidden" name="action" value="create_silo">
        <div class="nd-form-grid">
            <div class="nd-form-row"><label class="nd-label">Silo ID *</label><input name="silo_id_code" class="nd-input" required maxlength="64" placeholder="e.g. SILO-ALPHA-01"></div>
            <div class="nd-form-row"><label class="nd-label">Silo Name *</label><input name="silo_name" class="nd-input" required maxlength="128" placeholder="e.g. Alpha Site"></div>
        </div>
        <div class="nd-form-row"><label class="nd-label">Location</label><input name="silo_location" class="nd-input" maxlength="256" placeholder="e.g. Northern Sector, Grid R-47"></div>
        <div class="nd-form-grid">
            <div class="nd-form-row"><label class="nd-label">Capacity (warheads)</label><input name="silo_capacity" class="nd-input" type="number" min="1" max="50" value="4"></div>
            <div class="nd-form-row"><label class="nd-label">Hardening Level</label>
                <select name="hardening_level" class="nd-select">
                    <option value="minimal">Minimal</option><option value="standard" selected>Standard</option>
                    <option value="hardened">Hardened</option><option value="superhardened">Super Hardened</option>
                    <option value="deep_underground">Deep Underground</option><option value="mobile">Mobile / TEL</option>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
            <button type="button" class="nd-btn nd-btn-outline" onclick="this.closest('.nd-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="nd-btn nd-btn-amber"><i class="fas fa-warehouse"></i> Construct Silo</button>
        </div>
    </form>
</div>
</div>

<!-- Change DEFCON Modal -->
<div class="nd-modal-bg" id="defconModal">
<div class="nd-modal">
    <h3><i class="fas fa-shield-halved"></i> Change DEFCON Level</h3>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_nuclear'] ?>">
        <input type="hidden" name="action" value="change_defcon">
        <div class="nd-form-grid">
            <div class="nd-form-row"><label class="nd-label">New DEFCON Level</label>
                <select name="defcon_level" class="nd-select">
                    <option value="5" <?= $currentDefcon===5?'selected':'' ?>>DEFCON 5 — Lowest Readiness</option>
                    <option value="4" <?= $currentDefcon===4?'selected':'' ?>>DEFCON 4 — Increased Intel</option>
                    <option value="3" <?= $currentDefcon===3?'selected':'' ?>>DEFCON 3 — Increase Readiness</option>
                    <option value="2" <?= $currentDefcon===2?'selected':'' ?>>DEFCON 2 — Next Step to War</option>
                    <option value="1" <?= $currentDefcon===1?'selected':'' ?>>DEFCON 1 — NUCLEAR WAR IMMINENT</option>
                </select>
            </div>
            <div class="nd-form-row"><label class="nd-label">Response Posture</label>
                <select name="defcon_posture" class="nd-select">
                    <option value="peacetime">Peacetime Operations</option>
                    <option value="elevated">Elevated Alert</option>
                    <option value="war_ready">War Ready</option>
                    <option value="launch_ready">Launch Ready</option>
                    <option value="maximum">Maximum Force Posture</option>
                </select>
            </div>
        </div>
        <div class="nd-form-row"><label class="nd-label">Reason *</label><textarea name="defcon_reason" class="nd-textarea" required placeholder="Explain the reason for the DEFCON change..."></textarea></div>
        <div class="nd-form-row"><label class="nd-label">Standing Orders</label><textarea name="standing_orders" class="nd-textarea" placeholder="Orders to all units under this DEFCON level..."></textarea></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
            <button type="button" class="nd-btn nd-btn-outline" onclick="this.closest('.nd-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="nd-btn nd-btn-red"><i class="fas fa-shield-halved"></i> Change DEFCON</button>
        </div>
    </form>
</div>
</div>

<!-- Authorize Launch Modal -->
<div class="nd-modal-bg" id="launchModal">
<div class="nd-modal">
    <h3><i class="fas fa-rocket"></i> Authorize Nuclear Launch</h3>
    <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:0.75rem;margin-bottom:1rem;font-size:0.85rem;color:var(--nd-red);">
        <strong>WARNING:</strong> This action authorizes the launch of a nuclear warhead. This is irreversible. Requires DEFCON 2 or lower.
    </div>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_nuclear'] ?>">
        <input type="hidden" name="action" value="authorize_launch">
        <div class="nd-form-row"><label class="nd-label">Select Armed Warhead *</label>
            <select name="warhead_id" class="nd-select" required>
                <option value="">Select warhead...</option>
                <?php foreach ($warheads as $w): ?>
                    <?php if ($w['status'] === 'armed'): ?>
                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['designation']) ?> — <?= $w['warhead_type'] ?> (<?= $w['yield_megatons'] ?> MT)</option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="nd-form-row"><label class="nd-label">Target Zone *</label><input name="target_zone" class="nd-input" required maxlength="100" placeholder="Target designation or grid reference"></div>
        <div class="nd-form-row"><label class="nd-label">Confirmation Code *</label>
            <input name="confirm_code" class="nd-input" required placeholder="CONFIRM-LAUNCH-<?= date('Ymd') ?>" style="font-family:monospace;letter-spacing:1px;">
            <div style="font-size:0.7rem;color:var(--nd-dim);margin-top:0.3rem;">Enter exactly: CONFIRM-LAUNCH-<?= date('Ymd') ?></div>
        </div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
            <button type="button" class="nd-btn nd-btn-outline" onclick="this.closest('.nd-modal-bg').classList.remove('open')">Abort</button>
            <button type="submit" class="nd-btn nd-btn-red" onclick="return confirm('FINAL CONFIRMATION: Are you absolutely sure you want to authorize this nuclear launch?');"><i class="fas fa-rocket"></i> AUTHORIZE LAUNCH</button>
        </div>
    </form>
</div>
</div>

<!-- Schedule Inspection Modal -->
<div class="nd-modal-bg" id="inspectModal">
<div class="nd-modal">
    <h3><i class="fas fa-clipboard-check"></i> Schedule Inspection</h3>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_nuclear'] ?>">
        <input type="hidden" name="action" value="schedule_inspection">
        <div class="nd-form-grid">
            <div class="nd-form-row"><label class="nd-label">Target Type</label>
                <select name="inspect_target_type" class="nd-select" id="inspTargetType" onchange="updateInspTargets()">
                    <option value="warhead">Warhead</option><option value="silo">Silo</option><option value="facility">Facility</option>
                </select>
            </div>
            <div class="nd-form-row"><label class="nd-label">Target</label>
                <select name="inspect_target_id" class="nd-select" id="inspTargetSelect">
                    <?php foreach ($warheads as $w): ?>
                    <option value="<?= $w['id'] ?>" data-type="warhead"><?= htmlspecialchars($w['designation']) ?></option>
                    <?php endforeach; ?>
                    <?php foreach ($silos as $s): ?>
                    <option value="<?= $s['id'] ?>" data-type="silo" style="display:none"><?= htmlspecialchars($s['silo_id'] . ' — ' . $s['silo_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="nd-form-grid">
            <div class="nd-form-row"><label class="nd-label">Inspection Type</label>
                <select name="inspect_type" class="nd-select">
                    <option value="routine">Routine</option><option value="surprise">Surprise</option><option value="pre-launch">Pre-Launch</option>
                    <option value="post-maintenance">Post-Maintenance</option><option value="safety">Safety</option><option value="annual">Annual</option>
                </select>
            </div>
            <div class="nd-form-row"><label class="nd-label">Scheduled Date *</label><input name="inspect_date" class="nd-input" type="date" required value="<?= date('Y-m-d') ?>"></div>
        </div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
            <button type="button" class="nd-btn nd-btn-outline" onclick="this.closest('.nd-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="nd-btn nd-btn-cyan"><i class="fas fa-clipboard-check"></i> Schedule</button>
        </div>
    </form>
</div>
</div>

<!-- Complete Inspection Modal -->
<div class="nd-modal-bg" id="completeInspModal">
<div class="nd-modal">
    <h3><i class="fas fa-check-double"></i> Complete Inspection</h3>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_nuclear'] ?>">
        <input type="hidden" name="action" value="complete_inspection">
        <input type="hidden" name="insp_id" id="complInspId" value="">
        <div class="nd-form-grid">
            <div class="nd-form-row"><label class="nd-label">Result</label>
                <select name="insp_result" class="nd-select">
                    <option value="passed">Passed</option><option value="failed">Failed</option><option value="deferred">Deferred</option>
                </select>
            </div>
            <div class="nd-form-row"><label class="nd-label">Risk Level</label>
                <select name="insp_risk" class="nd-select">
                    <option value="none">None</option><option value="low">Low</option><option value="moderate">Moderate</option>
                    <option value="high">High</option><option value="critical">Critical</option>
                </select>
            </div>
        </div>
        <div class="nd-form-row"><label class="nd-label">Findings</label><textarea name="insp_findings" class="nd-textarea" placeholder="Document findings, issues, recommendations..."></textarea></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
            <button type="button" class="nd-btn nd-btn-outline" onclick="this.closest('.nd-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="nd-btn nd-btn-green"><i class="fas fa-check"></i> Complete</button>
        </div>
    </form>
</div>
</div>

<!-- Strike Simulation Modal -->
<div class="nd-modal-bg" id="simModal">
<div class="nd-modal">
    <h3><i class="fas fa-crosshairs"></i> Run Strike Simulation</h3>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_nuclear'] ?>">
        <input type="hidden" name="action" value="run_simulation">
        <div class="nd-form-grid">
            <div class="nd-form-row"><label class="nd-label">Simulation Name *</label><input name="sim_name" class="nd-input" required maxlength="200" placeholder="e.g. Pacific Deterrence Test"></div>
            <div class="nd-form-row"><label class="nd-label">Codename *</label><input name="sim_codename" class="nd-input" required maxlength="100" placeholder="e.g. THUNDERSTRIKE"></div>
        </div>
        <div class="nd-form-grid">
            <div class="nd-form-row"><label class="nd-label">Scenario Type</label>
                <select name="scenario_type" class="nd-select">
                    <option value="retaliatory">Retaliatory</option><option value="first_strike">First Strike</option>
                    <option value="counter_value">Counter Value</option><option value="counter_force">Counter Force</option>
                    <option value="demonstration">Demonstration</option><option value="emp_burst">EMP Burst</option>
                    <option value="decapitation">Decapitation</option>
                </select>
            </div>
            <div class="nd-form-row"><label class="nd-label">Warheads Used</label><input name="warheads_used" class="nd-input" type="number" min="1" max="999" value="1"></div>
        </div>
        <div class="nd-form-row"><label class="nd-label">Target Description</label><textarea name="target_desc" class="nd-textarea" placeholder="Describe the theoretical target and rationale..."></textarea></div>
        <div class="nd-form-grid">
            <div class="nd-form-row"><label class="nd-label">Est. Yield (MT)</label><input name="est_yield" class="nd-input" type="number" step="0.01" min="0" value="0"></div>
            <div class="nd-form-row"><label class="nd-label">Est. Casualties</label><input name="est_casualties" class="nd-input" type="number" min="0" value="0"></div>
            <div class="nd-form-row"><label class="nd-label">Collateral Radius (km)</label><input name="collateral_km" class="nd-input" type="number" step="0.1" min="0" value="0"></div>
        </div>
        <div class="nd-form-row"><label class="nd-label">Duration (minutes)</label><input name="sim_duration" class="nd-input" type="number" min="1" value="30"></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
            <button type="button" class="nd-btn nd-btn-outline" onclick="this.closest('.nd-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="nd-btn nd-btn-amber"><i class="fas fa-crosshairs"></i> Launch Simulation</button>
        </div>
    </form>
</div>
</div>

<!-- Complete Simulation Modal -->
<div class="nd-modal-bg" id="completeSimModal">
<div class="nd-modal">
    <h3><i class="fas fa-flag-checkered"></i> Conclude Simulation</h3>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_nuclear'] ?>">
        <input type="hidden" name="action" value="complete_simulation">
        <input type="hidden" name="sim_id" id="complSimId" value="">
        <div class="nd-form-row"><label class="nd-label">Outcome</label>
            <select name="sim_outcome" class="nd-select">
                <option value="success">Success</option><option value="partial">Partial Success</option>
                <option value="failure">Failure</option><option value="intercepted">Intercepted</option>
                <option value="aborted">Aborted</option>
            </select>
        </div>
        <div class="nd-form-row"><label class="nd-label">Lessons Learned</label><textarea name="sim_lessons" class="nd-textarea" placeholder="Key takeaways, weaknesses identified, improvements needed..."></textarea></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
            <button type="button" class="nd-btn nd-btn-outline" onclick="this.closest('.nd-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="nd-btn nd-btn-green"><i class="fas fa-flag-checkered"></i> Conclude</button>
        </div>
    </form>
</div>
</div>

<script>
// Close modals on backdrop click
document.querySelectorAll('.nd-modal-bg').forEach(bg => {
    bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); });
});

// Update inspection target dropdown based on type
function updateInspTargets() {
    const t = document.getElementById('inspTargetType').value;
    document.querySelectorAll('#inspTargetSelect option').forEach(o => {
        o.style.display = (o.dataset.type === t) ? '' : 'none';
        if (o.dataset.type !== t) o.selected = false;
    });
    const first = document.querySelector('#inspTargetSelect option[data-type="'+t+'"]');
    if (first) first.selected = true;
}

// Open complete inspection modal
function openCompleteInspection(id) {
    document.getElementById('complInspId').value = id;
    document.getElementById('completeInspModal').classList.add('open');
}

// Open complete simulation modal
function openCompleteSim(id) {
    document.getElementById('complSimId').value = id;
    document.getElementById('completeSimModal').classList.add('open');
}
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
