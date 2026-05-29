<?php
session_start();
require_once 'config/db.php';

// Route enforcement: Ensure only validated administrators can access this space
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

function resolveJobCardPrimaryKey(PDO $pdo) {
    $columns_stmt = $pdo->query("SHOW COLUMNS FROM job_cards");
    $columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('job_card_id', $columns)) {
        return 'job_card_id';
    }
    if (in_array('id', $columns)) {
        return 'id';
    }
    return $columns[0] ?? 'job_card_id';
}

function findLeastBusyMechanic(PDO $pdo) {
    $sql = "SELECT u.id, u.name
            FROM users u
            LEFT JOIN (
                SELECT mechanic_id, COUNT(*) AS active_count
                FROM job_cards
                WHERE status IN ('PENDING', 'IN_PROGRESS')
                GROUP BY mechanic_id
            ) j ON u.id = j.mechanic_id
            WHERE UPPER(u.role) = 'MECHANIC'
            ORDER BY COALESCE(j.active_count, 0) ASC, u.id ASC
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function assignJobToMechanic(PDO $pdo, string $job_card_id, int $mechanic_id) {
    $pk_col = resolveJobCardPrimaryKey($pdo);
    $stmt = $pdo->prepare("UPDATE job_cards SET mechanic_id = ?, status = 'IN_PROGRESS' WHERE {$pk_col} = ?");
    return $stmt->execute([$mechanic_id, $job_card_id]);
}

// Handle Mechanic Assignment Update and Auto Assignment Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $job_card_id = trim($_POST['job_card_id'] ?? '');

    if (empty($job_card_id) && $action !== 'auto_assign_all') {
        $error = 'Invalid job selection. Please refresh and try again.';
    } else {
        try {
            if ($action === 'assign_mechanic') {
                $mechanic_id = intval($_POST['mechanic_id']);
                if ($mechanic_id <= 0) {
                    throw new Exception('Please select a valid mechanic.');
                }
                assignJobToMechanic($pdo, $job_card_id, $mechanic_id);
                $message = 'Mechanic successfully allocated to job record!';
            } elseif ($action === 'auto_assign_mechanic') {
                $mechanic = findLeastBusyMechanic($pdo);
                if (!$mechanic) {
                    throw new Exception('No active mechanics available for auto assignment.');
                }
                assignJobToMechanic($pdo, $job_card_id, intval($mechanic['id']));
                $message = 'Job automatically assigned to ' . htmlspecialchars($mechanic['name']) . '.';
            } elseif ($action === 'auto_assign_all') {
                $select_stmt = $pdo->prepare("SELECT job_card_id FROM job_cards WHERE mechanic_id IS NULL AND status = 'PENDING' ORDER BY created_at ASC");
                $select_stmt->execute();
                $pending_jobs = $select_stmt->fetchAll(PDO::FETCH_COLUMN);

                if (empty($pending_jobs)) {
                    $message = 'No pending unassigned jobs were found for bulk assignment.';
                } else {
                    foreach ($pending_jobs as $pending_job_id) {
                        $mechanic = findLeastBusyMechanic($pdo);
                        if (!$mechanic) {
                            break;
                        }
                        assignJobToMechanic($pdo, $pending_job_id, intval($mechanic['id']));
                    }
                    $message = 'Bulk auto-assignment completed across available mechanics.';
                }
            }
        } catch (Exception $e) {
            $error = 'Assignment Failed: ' . $e->getMessage();
        }
    }
}

// Fetch all mechanics safely for the assignment dropdown menu
try {
    $mech_stmt = $pdo->prepare("SELECT id, name FROM users WHERE UPPER(role) = 'MECHANIC'");
    $mech_stmt->execute();
    $mechanics = $mech_stmt->fetchAll();
} catch (PDOException $e) {
    $mechanics = [];
}

// SAFE AUTOMATIC COLUMN QUERY BLOCK
try {
    // Dynamically look at your actual table schema columns to prevent query crashes
    $columns_stmt = $pdo->query("SHOW COLUMNS FROM job_cards");
    $existing_columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Choose whichever ID column exists in your schema setup
    $id_column = in_array('job_card_id', $existing_columns) ? 'job_card_id' : (in_array('id', $existing_columns) ? 'id' : $existing_columns[0]);

    // Build query safely using the verified existing primary tracking column
    $query = "SELECT j.*, m.name AS mechanic_name 
              FROM job_cards j
              LEFT JOIN users m ON j.mechanic_id = m.id
              ORDER BY j.{$id_column} DESC";
              
    $job_stmt = $pdo->query($query);
    $jobs = $job_stmt->fetchAll();
} catch (PDOException $e) {
    $jobs = [];
    $error = "Database Schema Alignment Failure: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>8099 PitStop | Workshop Job Cards</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="icon" href="includes/image/8099LO.png" type="image/png">
    <link rel="license" href="LICENSE">
    <meta name="author" content="Bibin Binu">
    <meta name="copyright" content="Copyright (c) <?= date('Y') ?> Bibin Binu">
    <meta name="license" content="MIT License">
    <style>
        :root {
            --primary-red: #E10600;
            --bg-dark: #0D0D0D;
            --card-glass: rgba(26, 26, 26, 0.76);
            --border-glass: rgba(255, 255, 255, 0.10);
            --neon-danger: #ff4d4d;
            --neon-success: #34d399;
            --neon-warning: #fbbf24;
            --neon-info: #38bdf8;
            --text-white: #F5F5F5;
            --text-muted: rgba(245, 245, 245, 0.72);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: var(--text-white);
            background: radial-gradient(circle at top, rgba(225, 6, 0, 0.13), transparent 20%),
                        radial-gradient(circle at top right, rgba(255, 255, 255, 0.05), transparent 25%),
                        linear-gradient(180deg, #060606 0%, #0d0d0d 100%);
            display: block;
            overflow-x: hidden;
        }

        .admin-shell {
            display: flex;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: repeating-linear-gradient(135deg, rgba(255,255,255,.02) 0 8px, transparent 8px 16px),
                              repeating-linear-gradient(45deg, rgba(255,255,255,.02) 0 4px, transparent 4px 8px);
            opacity: 0.1;
            pointer-events: none;
        }

        /* Sidebar Glass Layout Navigation */
        .sidebar {
            flex: 0 0 280px;
            width: 280px;
            background: rgba(13, 13, 13, 0.85);
            border-right: 1px solid var(--border-glass);
            backdrop-filter: blur(20px);
            display: flex;
            flex-direction: column;
            padding: 30px 20px;
            box-sizing: border-box;
            position: relative;
            top: auto;
            height: auto;
        }

        .sidebar-brand {
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            margin-bottom: 30px;
        }

        .sidebar-brand img {
            width: 100%;
            max-width: 180px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sidebar-item a {
            display: flex;
            align-items: center;
            gap: 14px;
            color: var(--text-muted);
            text-decoration: none;
            padding: 14px 18px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .sidebar-item a:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255,255,255,0.06);
        }

        .sidebar-item.active a {
            background: rgba(225, 6, 0, 0.12);
            color: #fff;
            border-color: rgba(225, 6, 0, 0.35);
            font-weight: 600;
            box-shadow: 0 0 15px rgba(225, 6, 0, 0.15);
        }

        .sidebar-item a i {
            font-size: 18px;
        }

        .sidebar-logout {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,.08);
            padding-top: 20px;
        }

        .sidebar-logout a {
            display: flex;
            align-items: center;
            gap: 14px;
            color: var(--neon-danger);
            text-decoration: none;
            padding: 14px 18px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .sidebar-logout a:hover {
            background: rgba(255, 77, 77, 0.08);
            border-radius: 12px;
        }

        /* Main Workspace Content Area */
        .main-content {
            flex: 1;
            min-width: 0;
            padding: 40px;
            width: auto;
            overflow-y: visible;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .header-section h1 {
            font-size: 2.2rem;
            margin: 0;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(90deg, #ffffff, var(--text-muted));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .panel-card {
            background: var(--card-glass);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 30px;
            backdrop-filter: blur(25px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .panel-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-red);
        }

        .panel-card h2 {
            font-size: 1.4rem;
            margin-top: 0;
            margin-bottom: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding-bottom: 12px;
        }

        /* Modern Table Layout */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        th {
            color: var(--text-muted);
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-glass);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            vertical-align: middle;
            color: #e2e8f0;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.01);
        }

        /* Status and Badges */
        .job-badge {
            font-family: monospace;
            background: rgba(255,255,255,0.08);
            padding: 4px 8px;
            border-radius: 6px;
            color: var(--neon-info);
            border: 1px solid rgba(255,255,255,0.06);
            font-weight: 700;
        }

        .status-pill {
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-pending {
            background: rgba(251, 191, 36, 0.12);
            border: 1px solid rgba(251, 191, 36, 0.2);
            color: var(--neon-warning);
        }

        .status-progress {
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.2);
            color: var(--neon-info);
        }

        .status-completed {
            background: rgba(52, 211, 153, 0.12);
            border: 1px solid rgba(52, 211, 153, 0.2);
            color: var(--neon-success);
        }

        /* Allocation form fields */
        .inline-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .select-input {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.10);
            color: #fff;
            padding: 10px 14px;
            font-size: 0.88rem;
            border-radius: 12px;
            transition: all 0.2s ease;
            outline: none;
            cursor: pointer;
        }

        .select-input:focus {
            border-color: rgba(225,6,0,.6);
            box-shadow: 0 0 15px rgba(225,6,0,.15);
        }

        select option {
            background-color: #1a1a1a;
            color: #ffffff;
        }

        .btn-update {
            background: linear-gradient(135deg, var(--primary-red), #ff2a24);
            color: #fff;
            border: none;
            padding: 10px 16px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.88rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(225, 6, 0, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-update:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(225, 6, 0, 0.4);
        }

        .btn-auto {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            padding: 10px 16px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.88rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-auto:hover {
            background: var(--primary-red);
            border-color: var(--primary-red);
            transform: translateY(-1px);
        }

        .btn-auto-all {
            background: linear-gradient(135deg, #FF8C00, #E10600);
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(225, 6, 0, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-auto-all:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(225, 6, 0, 0.4);
        }

        .alert-box {
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
            color: var(--neon-success);
            border-color: rgba(52, 211, 153, 0.2);
        }

        .alert-danger {
            background: rgba(255, 77, 77, 0.1);
            color: var(--neon-danger);
            border-color: rgba(255, 77, 77, 0.2);
        }

        @media (max-width: 992px) {
            .admin-shell { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: static; padding: 20px; }
            .main-content { max-width: 100%; padding: 20px; }
        }
    </style>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="admin-shell">

    <!-- Sidebar Glass Layout Navigation -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            <a href="index.php">
                <img src="includes/image/8099LO.png" alt="8099 PitStop">
            </a>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="admin_dash.php"><i class='bx bxs-dashboard'></i> Command Center</a></li>
            <li class="sidebar-item"><a href="inventory.php"><i class='bx bx-package'></i> Parts Inventory</a></li>
            <li class="sidebar-item active"><a href="job_cards.php"><i class='bx bx-wrench'></i> Live Job Cards</a></li>
        </ul>
        
        <div class="sidebar-logout">
            <a href="logout.php"><i class='bx bx-log-out-circle'></i> Exit System</a>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="header-section">
            <div>
                <h1>Live Workshop Job Cards</h1>
                <p style="color: var(--text-muted); margin: 5px 0 0 0;">Allocate unassigned vehicles to lead specialists and balance mechanical team loads</p>
            </div>
            
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="auto_assign_all">
                <button type="submit" class="btn-auto-all"><i class='bx bx-shuffle'></i> Auto Assign All Pending</button>
            </form>
        </div>

        <!-- Alert Notifications -->
        <?php if (!empty($message)): ?>
            <div class="alert-box alert-success">
                <i class='bx bx-check-circle' style="font-size: 20px;"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert-box alert-danger">
                <i class='bx bx-error-circle' style="font-size: 20px;"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Active Jobs Table Card -->
        <div class="panel-card" style="margin-bottom: 0;">
            <h2><i class='bx bx-time-five' style="color: var(--primary-red);"></i> Telemetry Feed & Mechanic Dispatch</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Job Entry ID</th>
                            <th>Vehicle Details</th>
                            <th>Diagnostics Brief</th>
                            <th>Lane Status</th>
                            <th style="text-align: right; padding-right: 30px;">Technician Allocation Dispatch</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($jobs) > 0): ?>
                            <?php foreach ($jobs as $job): ?>
                                <?php 
                                    $current_id = $job[$id_column];
                                    $vehicle_display = $job['vehicle_number'] ?? $job['vehicle_id'] ?? $job['license_plate'] ?? $job['vehicle_no'] ?? 'Registered Car';
                                    $desc_display = $job['description'] ?? $job['problem'] ?? $job['issue'] ?? $job['repair_notes'] ?? 'General Repair Maintenance';
                                    $status_val = strtoupper($job['status'] ?? 'PENDING');
                                    
                                    $status_class = 'status-pending';
                                    $status_label = 'Standby';
                                    if ($status_val === 'IN_PROGRESS' || $status_val === 'PROGRESS') {
                                        $status_class = 'status-progress';
                                        $status_label = 'In Progress';
                                    } elseif ($status_val === 'COMPLETED' || $status_val === 'DONE') {
                                        $status_class = 'status-completed';
                                        $status_label = 'Tuned';
                                    }
                                ?>
                                <tr>
                                    <td><span class="job-badge">#<?= htmlspecialchars($current_id) ?></span></td>
                                    <td>
                                        <strong style="color: #fff; font-size: 14px;"><?= htmlspecialchars($vehicle_display) ?></strong><br>
                                        <span style="font-size: 11px; color: var(--text-muted);">Lead Staff: <strong><?= htmlspecialchars($job['mechanic_name'] ?? 'Queue Standby') ?></strong></span>
                                    </td>
                                    <td style="max-width: 320px; font-weight: 500;"><?= htmlspecialchars($desc_display) ?></td>
                                    <td>
                                        <span class="status-pill <?= $status_class ?>">
                                            <i class='bx <?= $status_val === 'PENDING' ? 'bx-time-five' : ($status_val === 'IN_PROGRESS' ? 'bx-wrench' : 'bx-check-double') ?>'></i>
                                            <?= $status_label ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                            <form method="POST" class="inline-form">
                                                <input type="hidden" name="action" value="assign_mechanic">
                                                <input type="hidden" name="job_card_id" value="<?= htmlspecialchars($current_id) ?>">
                                                
                                                <select name="mechanic_id" class="select-input" required>
                                                    <option value="">-- Assign Staff --</option>
                                                    <?php foreach ($mechanics as $mech): ?>
                                                        <option value="<?= $mech['id'] ?>" <?= (isset($job['mechanic_id']) && $job['mechanic_id'] == $mech['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($mech['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                
                                                <button type="submit" class="btn-update"><i class='bx bx-user-check'></i> Assign</button>
                                            </form>

                                            <form method="POST" style="margin: 0;">
                                                <input type="hidden" name="action" value="auto_assign_mechanic">
                                                <input type="hidden" name="job_card_id" value="<?= htmlspecialchars($current_id) ?>">
                                                <button type="submit" class="btn-auto" title="Auto-assign least busy technician"><i class='bx bx-shuffle'></i> Auto</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;">No vehicles currently registered in the live pit lane feeds.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>