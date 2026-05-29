<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Ensure only logged-in CUSTOMERS can access this workspace
checkRole('CUSTOMER');

$current_user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Handle Adding a New Vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_vehicle') {
    $raw_plate = trim($_POST['vehicle_no'] ?? '');
    $validated_plate = validateIndianPlate($raw_plate);

    if (!$validated_plate) {
        $error_msg = 'Invalid Indian Number Plate format! Use standard formats like KA01AB1234 without spaces.';
    } else {
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $category = $_POST['category'] ?? '';
        $fuel_type = $_POST['fuel_type'] ?? '';

        try {
            // Check if vehicle already exists in the system
            $check_stmt = $pdo->prepare('SELECT vehicle_no FROM vehicles WHERE vehicle_no = ?');
            $check_stmt->execute([$validated_plate]);
            
            if ($check_stmt->fetch()) {
                $error_msg = 'This vehicle registration number is already registered in our system.';
            } else {
                $ins_stmt = $pdo->prepare('INSERT INTO vehicles (vehicle_no, customer_id, brand, model, category, fuel_type) VALUES (?, ?, ?, ?, ?, ?)');
                $ins_stmt->execute([$validated_plate, $current_user_id, $brand, $model, $category, $fuel_type]);
                $success_msg = 'Vehicle successfully added to your 8099 PitStop Virtual Garage!';
            }
        } catch (PDOException $e) {
            $error_msg = 'Database error: ' . $e->getMessage();
        }
    }
}

function generateJobCardId(PDO $pdo) {
    do {
        $job_id = 'PS-' . date('YmdHis') . '-' . random_int(1000, 9999);
        $check_stmt = $pdo->prepare('SELECT job_card_id FROM job_cards WHERE job_card_id = ?');
        $check_stmt->execute([$job_id]);
    } while ($check_stmt->fetch());

    return $job_id;
}

function findLeastBusyMechanic(PDO $pdo) {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.name
         FROM users u
         LEFT JOIN (
             SELECT mechanic_id, COUNT(*) AS active_count
             FROM job_cards
             WHERE status IN ('PENDING', 'IN_PROGRESS')
             GROUP BY mechanic_id
         ) j ON u.id = j.mechanic_id
         WHERE UPPER(u.role) = 'MECHANIC'
         ORDER BY COALESCE(j.active_count, 0) ASC, u.id ASC
         LIMIT 1"
    );
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_service') {
    $vehicle_no = trim($_POST['vehicle_no'] ?? '');
    $service_type = trim($_POST['service_type'] ?? '');
    $service_notes = trim($_POST['service_notes'] ?? '');
    $selected_mechanic_id = intval($_POST['mechanic_id'] ?? 0);

    if ($vehicle_no === '' || $service_type === '') {
        $error_msg = 'Select a vehicle and choose the service required before booking.';
    } else {
        try {
            $job_card_id = generateJobCardId($pdo);
            $repair_notes = $service_type;
            if ($service_notes !== '') {
                $repair_notes .= ' - ' . $service_notes;
            }

            $mechanic_id = null;
            $assigned_mechanic_name = null;

            if ($selected_mechanic_id > 0) {
                $check_stmt = $pdo->prepare('SELECT id, name FROM users WHERE id = ? AND UPPER(role) = ?');
                $check_stmt->execute([$selected_mechanic_id, 'MECHANIC']);
                $mechanic = $check_stmt->fetch(PDO::FETCH_ASSOC);
                if (!$mechanic) {
                    throw new Exception('Selected mechanic is not available. Please choose a valid technician.');
                }
                $mechanic_id = intval($mechanic['id']);
                $assigned_mechanic_name = $mechanic['name'];
            } else {
                $mechanic = findLeastBusyMechanic($pdo);
                if ($mechanic) {
                    $mechanic_id = intval($mechanic['id']);
                    $assigned_mechanic_name = $mechanic['name'];
                }
            }

            if ($mechanic_id !== null) {
                $service_stmt = $pdo->prepare('INSERT INTO job_cards (job_card_id, vehicle_no, mechanic_id, repair_notes, status) VALUES (?, ?, ?, ?, ? )');
                $service_stmt->execute([$job_card_id, $vehicle_no, $mechanic_id, $repair_notes, 'PENDING']);
                $success_msg = 'Your service request has been submitted and assigned to ' . htmlspecialchars($assigned_mechanic_name) . '. The technician will accept the order shortly.';
            } else {
                $service_stmt = $pdo->prepare('INSERT INTO job_cards (job_card_id, vehicle_no, mechanic_id, repair_notes, status) VALUES (?, ?, NULL, ?, ? )');
                $service_stmt->execute([$job_card_id, $vehicle_no, $repair_notes, 'PENDING']);
                $success_msg = 'Your service request has been submitted and will be auto-assigned once a mechanic is available.';
            }
        } catch (PDOException $e) {
            $error_msg = 'Unable to book service: ' . $e->getMessage();
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
        }
    }
}

// Fetch all vehicles belonging to this logged-in customer
$veh_stmt = $pdo->prepare('SELECT * FROM vehicles WHERE customer_id = ?');
$veh_stmt->execute([$current_user_id]);
$my_vehicles = $veh_stmt->fetchAll();

try {
    $mech_stmt = $pdo->prepare('SELECT id, name FROM users WHERE role = ? ORDER BY name ASC');
    $mech_stmt->execute(['MECHANIC']);
    $mechanics = $mech_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mechanics = [];
}

// Fetch customer's service requests
$req_stmt = $pdo->prepare(
    'SELECT j.job_card_id, j.vehicle_no, j.repair_notes, j.status, j.created_at, u.name AS mechanic_name
     FROM job_cards j
     JOIN vehicles v ON j.vehicle_no = v.vehicle_no
     LEFT JOIN users u ON j.mechanic_id = u.id
     WHERE v.customer_id = ?
     ORDER BY j.created_at DESC'
);
$req_stmt->execute([$current_user_id]);
$service_requests = $req_stmt->fetchAll();

// Include the premium glassmorphic header layout
include 'includes/header.php';
?>

<style>
    .portal-layout {
        display: grid;
        grid-template-columns: 1.1fr 1.9fr;
        gap: 30px;
        margin-top: 20px;
    }
    @media (max-width: 1024px) {
        .portal-layout { grid-template-columns: 1fr; }
    }
    
    .dashboard-header {
        margin-bottom: 30px;
    }
    .dashboard-header h2 {
        font-size: 2.2rem;
        font-weight: 800;
        margin: 0 0 8px 0;
        letter-spacing: -0.5px;
        background: linear-gradient(90deg, #ffffff, rgba(255,255,255,0.7));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 18px;
    }

    .form-group label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-muted);
        font-weight: 600;
    }

    .form-control, select, textarea {
        width: 100%;
        padding: 14px 16px;
        border-radius: 14px;
        border: 1px solid rgba(255,255,255,.10);
        background: rgba(255,255,255,.04);
        color: #fff;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }

    .form-control:focus, select:focus, textarea:focus {
        outline: none;
        border-color: rgba(225,6,0,.6);
        box-shadow: 0 0 15px rgba(225,6,0,.15);
        background: rgba(255,255,255,.07);
    }
    
    select option {
        background-color: #1a1a1a;
        color: #ffffff;
    }

    /* Garage grid and vehicle cards */
    .garage-section {
        margin-top: 30px;
    }
    .garage-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
        margin-top: 20px;
    }
    
    .vehicle-card {
        background: rgba(18, 18, 18, 0.96);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        transition: all 0.25s ease;
    }
    .vehicle-card::before {
        content: '';
        position: absolute;
        top: -15px;
        right: -15px;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(225, 6, 0, 0.08);
        transition: background 0.25s ease;
    }
    .vehicle-card:hover {
        transform: translateY(-2px);
        border-color: rgba(225, 6, 0, 0.45);
        box-shadow: 0 12px 30px rgba(225, 6, 0, 0.15);
    }
    .vehicle-card:hover::before {
        background: rgba(225, 6, 0, 0.18);
    }

    .vehicle-icon {
        font-size: 32px;
        color: var(--primary-red);
        margin-bottom: 16px;
    }

    .plate-badge {
        background: #facc15;
        color: #000;
        font-family: 'Courier New', monospace;
        font-weight: 800;
        padding: 6px 12px;
        border-radius: 6px;
        display: inline-block;
        margin-top: 14px;
        letter-spacing: 1.5px;
        border: 1.5px solid #000;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    /* Modern Table Layout */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        margin-top: 20px;
    }

    .service-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .service-table th {
        color: var(--text-muted);
        padding: 16px;
        border-bottom: 1px solid var(--border-glass);
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-weight: 700;
    }

    .service-table td {
        padding: 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        font-size: 14px;
        color: #e5e7eb;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-pending {
        background: rgba(251, 191, 36, 0.12);
        border: 1px solid rgba(251, 191, 36, 0.2);
        color: #fbbf24;
    }
    .status-in_progress {
        background: rgba(56, 189, 248, 0.12);
        border: 1px solid rgba(56, 189, 248, 0.2);
        color: #38bdf8;
    }
    .status-completed {
        background: rgba(52, 211, 153, 0.12);
        border: 1px solid rgba(52, 211, 153, 0.2);
        color: #34d399;
    }
    .status-delivered {
        background: rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.2);
        color: #10b981;
    }

    .alert {
        padding: 16px 24px;
        border-radius: 16px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
        border: 1px solid transparent;
    }

    .alert-success {
        background: rgba(52, 211, 153, 0.1);
        color: #34d399;
        border-color: rgba(52, 211, 153, 0.2);
    }

    .alert-danger {
        background: rgba(255, 77, 77, 0.1);
        color: #ff4d4d;
        border-color: rgba(255, 77, 77, 0.2);
    }
</style>

<div class="container" style="padding: 0 10px;">
    <div class="dashboard-header">
        <h2>My Virtual Garage Portal</h2>
        <p style="color: var(--text-muted); margin: 0;">Manage your premium vehicles, check active diagnostic states, and book F1-grade pit stop servicing.</p>
    </div>

    <!-- Feedback Banners -->
    <?php if ($success_msg): ?>
        <div class="alert alert-success">
            <i class='bx bx-check-circle' style="font-size: 20px;"></i>
            <span><?= htmlspecialchars($success_msg) ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger">
            <i class='bx bx-error-circle' style="font-size: 20px;"></i>
            <span><?= htmlspecialchars($error_msg) ?></span>
        </div>
    <?php endif; ?>

    <div class="portal-layout">
        <!-- Add Vehicle Form Column -->
        <div class="glass-card neon-hover" style="border-radius: 28px; padding: 32px;">
            <h3 style="margin-top: 0; margin-bottom: 22px; font-weight: 800; font-size: 1.4rem; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 12px;">
                <i class='bx bx-plus-circle' style="color: var(--primary-red);"></i> Add New Vehicle
            </h3>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add_vehicle">
                
                <div class="form-group">
                    <label>Registration Number (Number Plate)</label>
                    <input type="text" name="vehicle_no" required class="form-control" placeholder="e.g. KA51MB8842 (without spaces)" style="text-transform: uppercase;">
                </div>
                
                <div class="form-group">
                    <label>Vehicle Type Category</label>
                    <select name="category" required>
                        <option value="BIKE">Two Wheeler (Superbike/Motorcycle)</option>
                        <option value="SCOOTER">Activa / Scooter Variant</option>
                        <option value="CAR">Performance Sedan / Hatchback</option>
                        <option value="SUV">Crossover / Heavy SUV</option>
                        <option value="EV">Electric Powertrain (EV)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Vehicle Manufacturer Brand</label>
                    <select name="brand" required>
                        <option value="Hero">Hero MotoCorp</option>
                        <option value="Bajaj">Bajaj Auto</option>
                        <option value="Honda">Honda Motorcycles/Cars</option>
                        <option value="TVS">TVS Motor</option>
                        <option value="Royal Enfield">Royal Enfield</option>
                        <option value="Yamaha">Yamaha Performance</option>
                        <option value="Maruti Suzuki">Maruti Suzuki</option>
                        <option value="Hyundai">Hyundai India</option>
                        <option value="Tata Motors">Tata Motors</option>
                        <option value="Mahindra">Mahindra & Mahindra</option>
                        <option value="Toyota">Toyota Kirloskar</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Model / Engine Trim</label>
                    <input type="text" name="model" required class="form-control" placeholder="e.g. R15 V4 / Fortuner Sigma 4">
                </div>

                <div class="form-group">
                    <label>Fuel Fuel System</label>
                    <select name="fuel_type" required>
                        <option value="PETROL">Petrol Powertrain</option>
                        <option value="DIESEL">Turbo Diesel</option>
                        <option value="CNG">Eco CNG</option>
                        <option value="ELECTRIC">Pure Battery Electric (BHSV)</option>
                        <option value="HYBRID">Intelligent Hybrid (MHSV)</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary" style="margin-top: 10px; width: 100%;"><i class='bx bx-plus'></i> Register Vehicle to Garage</button>
            </form>
        </div>

        <!-- Book Service Form Column -->
        <div class="glass-card neon-hover" style="border-radius: 28px; padding: 32px;">
            <h3 style="margin-top: 0; margin-bottom: 22px; font-weight: 800; font-size: 1.4rem; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 12px;">
                <i class='bx bx-check-circle' style="color: var(--primary-red);"></i> Book Required Service
            </h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0; margin-bottom: 24px;">Select from your fleet of registered vehicles and choose an F1 tune-up package.</p>

            <?php if (empty($my_vehicles)): ?>
                <div style="background: rgba(255,255,255,0.03); border: 1px dashed rgba(255,255,255,0.12); padding: 30px; border-radius: 16px; text-align: center; color: var(--text-muted);">
                    <i class='bx bx-info-circle' style="font-size: 32px; color: var(--primary-red); margin-bottom: 12px;"></i>
                    <p style="margin: 0;">No vehicles registered under your account yet. Register your vehicle on the left to initiate service orders.</p>
                </div>
            <?php else: ?>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="book_service">
                    
                    <div class="form-group">
                        <label for="vehicle_no">Select Registered Vehicle</label>
                        <select id="vehicle_no" name="vehicle_no" required>
                            <option value="">-- Choose Vehicle --</option>
                            <?php foreach ($my_vehicles as $veh): ?>
                                <option value="<?= htmlspecialchars($veh['vehicle_no']) ?>"><?= htmlspecialchars($veh['vehicle_no'] . ' — ' . $veh['brand'] . ' ' . $veh['model']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="service_type">Select Service Operations Package</label>
                        <select id="service_type" name="service_type" required>
                            <option value="">-- Choose Package --</option>
                            <option value="Full Performance Service">Full Performance Service (Elite Package)</option>
                            <option value="Engine Tune-Up">Engine Calibration & Performance Tuning</option>
                            <option value="Brake Inspection & Repair">High-Performance Brake Service</option>
                            <option value="Suspension & Alignment">Racing Track Suspension Tuning</option>
                            <option value="EV Battery Health Check">EV Core Battery Diagnostic</option>
                            <option value="Oil Change & Filter Replacement">Liquid Gold Engine Oil Flush</option>
                            <option value="Transmission Service">Gearbox Calibration</option>
                            <option value="Custom Diagnostic Request">Telemetry Diagnostic Run</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="mechanic_id">Preferred Mechanic (Optional)</label>
                        <select id="mechanic_id" name="mechanic_id">
                            <option value="">-- Auto Assign to Available Mechanic --</option>
                            <?php foreach ($mechanics as $mechanic): ?>
                                <option value="<?= intval($mechanic['id']) ?>"><?= htmlspecialchars($mechanic['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--text-muted); font-size: 0.85rem;">Leave blank to auto-assign the least busy available technician.</small>
                    </div>
                    <div class="form-group">
                        <label for="service_notes">Special Technical Directives / Symptoms</label>
                        <textarea id="service_notes" name="service_notes" placeholder="Describe any vibrations, leaks, or specific tuning you require..." style="min-height: 100px;"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-primary" style="width: 100%;"><i class='bx bx-check-shield'></i> Authorize Service Dispatch</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Garage Fleet Overview -->
    <div class="garage-section">
        <div class="glass-card neon-hover" style="border-radius: 28px; padding: 32px; margin-bottom: 30px;">
            <h3 style="margin-top: 0; margin-bottom: 12px; font-weight: 800; font-size: 1.4rem; display: flex; align-items: center; gap: 10px;">
                <i class='bx bx-grid-alt' style="color: var(--primary-red);"></i> Active Garage Fleet
            </h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0; margin-bottom: 24px;">Your registered high-performance vehicle profiles currently on standby</p>
            
            <?php if (empty($my_vehicles)): ?>
                <div style="background: rgba(255,255,255,0.03); border: 1px dashed rgba(255,255,255,0.12); padding: 40px; border-radius: 20px; text-align: center; color: var(--text-muted);">
                    <i class='bx bx-car' style="font-size: 40px; color: var(--primary-red); margin-bottom: 14px;"></i>
                    <p style="margin: 0; font-size: 1rem;">Virtual garage space is currently clear.</p>
                </div>
            <?php else: ?>
                <div class="garage-grid">
                    <?php foreach ($my_vehicles as $veh): ?>
                        <div class="vehicle-card">
                            <div class="vehicle-icon">
                                <?php if ($veh['category'] === 'BIKE' || $veh['category'] === 'SCOOTER'): ?>
                                    <i class='bx bx-cycling'></i>
                                <?php else: ?>
                                    <i class='bx bx-car'></i>
                                <?php endif; ?>
                            </div>
                            <h4 style="margin-top: 0; margin-bottom: 6px; font-size: 1.25rem; font-weight: 700;"><?= htmlspecialchars($veh['brand'] . ' ' . $veh['model']) ?></h4>
                            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 600;">
                                Category: <?= $veh['category'] ?> | <?= $veh['fuel_type'] ?>
                            </span>
                            <br>
                            <div class="plate-badge"><?= htmlspecialchars($veh['vehicle_no']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Service Records & Telemetry Tracking -->
    <?php if (!empty($service_requests)): ?>
        <div class="glass-card neon-hover" style="border-radius: 28px; padding: 32px;">
            <h3 style="margin-top: 0; margin-bottom: 12px; font-weight: 800; font-size: 1.4rem; display: flex; align-items: center; gap: 10px;">
                <i class='bx bx-history' style="color: var(--primary-red);"></i> Telemetry Status & History
            </h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0; margin-bottom: 24px;">Track active servicing, check technician assignments, and review historical logs</p>
            
            <div class="table-responsive">
                <table class="service-table">
                    <thead>
                        <tr>
                            <th>Job Code ID</th>
                            <th>Vehicle ID</th>
                            <th>Service Specifications</th>
                            <th>Pit Lane Status</th>
                            <th>Assigned Lead</th>
                            <th>Invoice</th>
                            <th>Log Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($service_requests as $request): ?>
                            <tr>
                                <td><span style="font-family: monospace; font-weight: 700; color: #fff; background: rgba(255,255,255,0.06); padding: 4px 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.08);">#<?= htmlspecialchars($request['job_card_id']) ?></span></td>
                                <td style="font-weight: 700; color: #fff;"><?= htmlspecialchars($request['vehicle_no']) ?></td>
                                <td><?= htmlspecialchars($request['repair_notes']) ?></td>
                                <td>
                                    <span class="status-pill status-<?= strtolower(str_replace(' ', '_', $request['status'])) ?>">
                                        <i class='bx <?= $request['status'] === 'PENDING' ? 'bx-time-five' : ($request['status'] === 'IN_PROGRESS' ? 'bx-wrench' : 'bx-check-double') ?>'></i>
                                        <?= htmlspecialchars($request['status'] === 'COMPLETED' ? 'DONE' : $request['status']) ?>
                                    </span>
                                </td>
                                <td style="font-weight: 600;"><?= htmlspecialchars($request['mechanic_name'] ?? 'Queue Standby') ?></td>
                                <td style="font-weight: 600;">
                                    <?php
                                        $inv_check = $pdo->prepare('SELECT invoice_no FROM invoices WHERE job_card_id = ?');
                                        $inv_check->execute([$request['job_card_id']]);
                                        $has_inv = $inv_check->fetchColumn();
                                    ?>
                                    <?php if ($has_inv): ?>
                                        <a href="invoices.php?job=<?= urlencode($request['job_card_id']) ?>" style="color: #fbbf24; font-weight: 700;">View Invoice</a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">Not yet billed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(date('d M Y', strtotime($request['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>