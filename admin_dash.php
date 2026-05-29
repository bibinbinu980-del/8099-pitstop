<?php
session_start();
require_once 'config/db.php';

// Route enforcement: Ensure only validated administrators can access this space
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
    header('Location: index.php');
    exit;
}

$success_message = '';
$error_message = '';
$customer_rows = [];
$mechanic_rows = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_mechanic') {
    $mechanic_id = intval($_POST['mechanic_id'] ?? 0);

    if ($mechanic_id <= 0) {
        $error_message = 'Invalid mechanic selected.';
    } else {
        try {
            // Ensure mechanic exists and is a MECHANIC
            $check_stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role = ?');
            $check_stmt->execute([$mechanic_id, 'MECHANIC']);

            if (!$check_stmt->fetch()) {
                $error_message = 'Mechanic not found or not a MECHANIC.';
            } else {
                // Remove user
                $del_stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role = ?');
                $del_stmt->execute([$mechanic_id, 'MECHANIC']);

                $success_message = 'Mechanic removed successfully.';
            }
        } catch (PDOException $e) {
            $error_message = 'Could not remove mechanic: ' . $e->getMessage();
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_mechanic') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $phone === '' || $email === '' || $password === '') {
        $error_message = 'Please fill out all fields when adding a new mechanic.';
    } else {
        try {
            $check_stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR phone = ?');
            $check_stmt->execute([$email, $phone]);

            if ($check_stmt->fetch()) {
                $error_message = 'A user with that email or phone number already exists.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $insert_stmt = $pdo->prepare('INSERT INTO users (name, phone, email, password, role) VALUES (?, ?, ?, ?, ?)');
                $insert_stmt->execute([$name, $phone, $email, $hashed_password, 'MECHANIC']);
                $success_message = 'Mechanic profile successfully created and ready for allocation.';
            }
        } catch (PDOException $e) {
            $error_message = 'Could not add mechanic: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_mechanic_password') {
    $mechanic_id = intval($_POST['mechanic_id'] ?? 0);
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($mechanic_id <= 0 || $password === '' || $confirm_password === '') {
        $error_message = 'Please select a mechanic and provide both password fields.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'New password and confirmation do not match.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } else {
        try {
            $check_stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role = ?');
            $check_stmt->execute([$mechanic_id, 'MECHANIC']);

            if (!$check_stmt->fetch()) {
                $error_message = 'Mechanic not found or not a valid mechanic account.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $update_stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ? AND role = ?');
                $update_stmt->execute([$hashed_password, $mechanic_id, 'MECHANIC']);
                $success_message = 'Mechanic password successfully updated.';
            }
        } catch (PDOException $e) {
            $error_message = 'Could not update mechanic password: ' . $e->getMessage();
        }
    }
}

// Fetch Quick Stats metrics from your exact MySQL schema structures
try {
    // 1. Total Registered Customers
    $customer_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'CUSTOMER'");
    $total_customers = $customer_stmt->fetchColumn() ?: 0;

    // 2. Total Registered Mechanics
    $mechanic_count_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'MECHANIC'");
    $total_mechanics = $mechanic_count_stmt->fetchColumn() ?: 0;

    // 3. Low Stock Parts Count matching your exact column structure (quantity <= low_stock_threshold)
    $stock_stmt = $pdo->query("SELECT COUNT(*) FROM inventory WHERE quantity <= COALESCE(low_stock_threshold, 5)");
    $low_stock = $stock_stmt->fetchColumn() ?: 0;

    // 4. Monthly Gross Workshop revenue tracking from invoices
    $rev_stmt = $pdo->query("SELECT SUM(total_payable) FROM invoices WHERE MONTH(invoice_date) = MONTH(CURRENT_DATE())");
    $monthly_revenue = $rev_stmt->fetchColumn() ?: 0;

    $customer_rows = $pdo->query("SELECT id, name, phone, email, address, created_at FROM users WHERE role = 'CUSTOMER' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $mechanic_rows = $pdo->query("SELECT id, name, phone, email, created_at FROM users WHERE role = 'MECHANIC' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Fall-safe default variables to prevent UI crashes if tables are adapting layout modifications
    $total_customers = 0;
    $total_mechanics = 0;
    $low_stock = 0;
    $monthly_revenue = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>8099 PitStop | Admin Control Hub</title>
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
            --neon-info: #38bdf8;
            --text-white: #F5F5F5;
            --text-muted: rgba(245, 245, 245, 0.72);
        }

        * { box-sizing: border-box; }

        body.admin-dashboard {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: var(--text-white);
            background: radial-gradient(circle at top, rgba(225, 6, 0, 0.13), transparent 20%),
                        radial-gradient(circle at top right, rgba(255, 255, 255, 0.05), transparent 25%),
                        linear-gradient(180deg, #060606 0%, #0d0d0d 100%);
            display: flex;
            flex-direction: row;
            align-items: stretch;
            overflow-x: hidden;
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
            min-height: auto;
            background: rgba(13, 13, 13, 0.95);
            border-right: 1px solid var(--border-glass);
            backdrop-filter: blur(20px);
            display: flex;
            flex-direction: column;
            padding: 30px 20px;
            box-sizing: border-box;
            position: relative;
            top: auto;
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
        .admin-dashboard .main-content {
            flex: 1;
            min-width: 0;
            padding: 40px;
            width: auto;
            overflow-y: visible;
            display: flex;
            flex-direction: column;
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

        .user-badge {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-glass);
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }

        .user-badge i { color: var(--primary-red); }

        /* Card Metric Display Blueprint grids */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 35px;
        }
        .metric-card {
            background: var(--card-glass);
            border: 1px solid var(--border-glass);
            backdrop-filter: blur(25px);
            border-radius: 24px;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            transition: all 0.25s ease;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            border-color: rgba(225, 6, 0, 0.35);
        }
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 3px;
            height: 100%;
            background: var(--border-glass);
        }
        .metric-card.alerted::before { background: var(--neon-danger); }
        .metric-card.success::before { background: var(--neon-success); }
        .metric-card.info::before { background: var(--neon-info); }

        .metric-info h3 {
            margin: 0 0 8px 0;
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
        }
        .metric-info p {
            margin: 0;
            font-size: 1.7rem;
            font-weight: 800;
            color: #fff;
        }

        .metric-icon-box {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .blue-icon { background: rgba(56, 189, 248, 0.08); color: var(--neon-info); border: 1px solid rgba(56, 189, 248, 0.15); }
        .red-icon { background: rgba(255, 77, 77, 0.08); color: var(--neon-danger); border: 1px solid rgba(255, 77, 77, 0.15); }
        .green-icon { background: rgba(52, 211, 153, 0.08); color: var(--neon-success); border: 1px solid rgba(52, 211, 153, 0.15); }

        .panel-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 26px;
            margin-bottom: 30px;
        }
        @media (max-width: 900px) {
            .panel-grid { grid-template-columns: 1fr; }
        }

        .data-panel {
            background: var(--card-glass);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 30px;
            position: relative;
        }
        .data-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-red);
            border-top-left-radius: 24px;
            border-bottom-left-radius: 24px;
        }

        .data-panel h2 {
            font-size: 1.3rem;
            margin-top: 0;
            margin-bottom: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding-bottom: 12px;
        }

        .field-row {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 18px;
        }
        .field-row label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            font-weight: 600;
        }
        .field-row input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.10);
            background: rgba(255,255,255,.04);
            color: #fff;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .field-row input:focus {
            outline: none;
            border-color: rgba(225,6,0,.6);
            box-shadow: 0 0 15px rgba(225,6,0,.15);
            background: rgba(255,255,255,.07);
        }

        .password-toggle-field {
            position: relative;
        }
        .password-toggle-field input {
            padding-right: 48px;
        }
        .password-toggle-field .toggle-password {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .password-toggle-field .toggle-password:hover {
            color: #fff;
        }

        .btn-assign {
            background: linear-gradient(135deg, var(--primary-red), #ff2a24);
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
            justify-content: center;
            gap: 8px;
            width: 100%;
        }
        .btn-assign:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(225, 6, 0, 0.4);
        }

        .mechanic-list-item {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 16px;
            padding: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }
        .mechanic-list-item:hover {
            background: rgba(255,255,255,.05);
            border-color: rgba(255,255,255,.1);
        }

        /* Modern Table Panel Layout */
        .table-panel {
            background: var(--card-glass);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow-x: auto;
        }
        .table-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-red);
            border-top-left-radius: 24px;
            border-bottom-left-radius: 24px;
        }
        .table-panel h2 {
            font-size: 1.3rem;
            margin-top: 0;
            margin-bottom: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding-bottom: 12px;
        }

        .customer-table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
        }
        .customer-table th {
            color: var(--text-muted);
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-glass);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
        }
        .customer-table td {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: #e2e8f0;
            font-size: 14px;
        }
        tr:hover td {
            background: rgba(255, 255, 255, 0.01);
        }

        .alert-panel {
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

        .panel-card {
            background: var(--card-glass);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 30px;
            position: relative;
        }
        .panel-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-red);
            border-top-left-radius: 24px;
            border-bottom-left-radius: 24px;
        }
        .panel-card h2 {
            font-size: 1.3rem;
            margin-top: 0;
            margin-bottom: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 992px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: static; padding: 20px; }
            .main-content { max-width: 100%; padding: 20px; }
        }
    </style>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="admin-dashboard">

    <!-- Sidebar Glass Layout Navigation -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            <a href="index.php">
                <img src="includes/image/8099LO.png" alt="8099 PitStop">
            </a>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-item active">
                <a href="admin_dash.php"><i class='bx bxs-dashboard'></i> Command Center</a>
            </li>
            <li class="sidebar-item">
                <a href="inventory.php"><i class='bx bx-package'></i> Parts Inventory</a>
            </li>
            <li class="sidebar-item">
                <a href="job_cards.php"><i class='bx bx-wrench'></i> Live Job Cards</a>
            </li>
        </ul>
        
        <div class="sidebar-logout">
            <a href="logout.php"><i class='bx bx-log-out-circle'></i> Exit System</a>
        </div>
    </nav>

    <!-- Main Workspace Content Area -->
    <main class="main-content">
        <header class="header-section">
            <div>
                <h1>Command Control Center Hub</h1>
                <p style="margin: 5px 0 0 0; color: var(--text-muted); font-size: 14px;">High-velocity operations telemetry oversight for 8099 PitStop</p>
            </div>
            <div class="user-badge">
                <i class='bx bxs-user-rectangle'></i>
                <span><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong> (ADMIN)</span>
            </div>
        </header>

        <!-- Notification Banner -->
        <?php if (!empty($success_message)): ?>
            <div class="alert-panel alert-success">
                <i class='bx bx-check-circle' style="font-size: 20px;"></i>
                <span><?= htmlspecialchars($success_message) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert-panel alert-danger">
                <i class='bx bx-error-circle' style="font-size: 20px;"></i>
                <span><?= htmlspecialchars($error_message) ?></span>
            </div>
        <?php endif; ?>

        <!-- Telemetry Stats Cards -->
        <section class="metrics-grid">
            <div class="metric-card info">
                <div class="metric-info">
                    <h3>Registered Customers</h3>
                    <p><?= $total_customers ?></p>
                </div>
                <div class="metric-icon-box blue-icon">
                    <i class='bx bx-user'></i>
                </div>
            </div>

            <div class="metric-card info">
                <div class="metric-info">
                    <h3>Mechanics Commissioned</h3>
                    <p><?= $total_mechanics ?></p>
                </div>
                <div class="metric-icon-box blue-icon">
                    <i class='bx bx-wrench'></i>
                </div>
            </div>

            <div class="metric-card success">
                <div class="metric-info">
                    <h3>Pit Lane Gross Revenue</h3>
                    <p>₹<?= number_format($monthly_revenue, 2) ?></p>
                </div>
                <div class="metric-icon-box green-icon">
                    <i class='bx bx-rupee'></i>
                </div>
            </div>

            <div class="metric-card alerted">
                <div class="metric-info">
                    <h3>Critical Spares Shortage</h3>
                    <p style="color: <?= $low_stock > 0 ? 'var(--neon-danger)' : '#fff' ?>;"><?= $low_stock ?></p>
                </div>
                <div class="metric-icon-box red-icon">
                    <i class='bx bx-package'></i>
                </div>
            </div>
        </section>

        <!-- Command Panel Grids -->
        <section class="panel-grid">
            <!-- Mechanic Commission Card -->
            <div class="data-panel">
                <h2><i class='bx bx-user-plus' style="color: var(--primary-red);"></i> Commission New Specialist</h2>
                <form method="POST" action="admin_dash.php">
                    <input type="hidden" name="action" value="add_mechanic">
                    <div class="field-row">
                        <label for="name">Mechanic Full Name</label>
                        <input id="name" name="name" type="text" placeholder="e.g. Sahana Patel" required>
                    </div>
                    <div class="field-row">
                        <label for="phone">Mobile Telemetry Contact</label>
                        <input id="phone" name="phone" type="text" placeholder="e.g. 9876543210" required>
                    </div>
                    <div class="field-row">
                        <label for="email">Staff Secure Email</label>
                        <input id="email" name="email" type="email" placeholder="e.g. sahana@pitstop.com" required>
                    </div>
                    <div class="field-row password-toggle-field">
                        <label for="password">Technician Master Password</label>
                        <input id="password" name="password" type="password" placeholder="Create a secure staff password" required>
                        <button type="button" class="toggle-password" data-target="password" aria-label="Toggle password visibility"><i class='bx bx-show'></i></button>
                    </div>
                    <button type="submit" class="btn-assign"><i class='bx bx-plus'></i> Commission Specialist</button>
                </form>
            </div>

            <!-- Current Mechanics Card -->
            <div class="data-panel" id="mechanics">
                <h2><i class='bx bx-user-check' style="color: var(--primary-red);"></i> Active Technical Team (<?= count($mechanic_rows) ?> staff)</h2>
                <?php if (empty($mechanic_rows)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 40px 0;">No active technician profiles are registered. Swivel to the left to add a member.</p>
                <?php else: ?>
                    <div style="display: grid; gap: 14px; max-height: 480px; overflow-y: auto; padding-right: 4px;">
                        <?php foreach ($mechanic_rows as $mech): ?>
                            <div class="mechanic-list-item">
                                <div>
                                    <strong style="color: #fff; font-size: 15px;"><?= htmlspecialchars($mech['name']) ?></strong>
                                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                                        <i class='bx bx-envelope' style="vertical-align: middle;"></i> <?= htmlspecialchars($mech['email']) ?>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 11px; background: rgba(52, 211, 153, 0.1); color: var(--neon-success); border: 1px solid rgba(52, 211, 153, 0.2); padding: 4px 10px; border-radius: 20px; font-weight: 700; text-transform: uppercase;">Active</span>
                                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;"><i class='bx bx-phone' style="vertical-align: middle;"></i> <?= htmlspecialchars($mech['phone']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="panel-card" style="margin-top: 24px;">
                <h2><i class='bx bx-key' style="color: var(--primary-red);"></i> Update Mechanic Password</h2>
                <p style="color: var(--text-muted); margin-bottom: 18px;">Select a mechanic and assign a new secure password.</p>
                <form method="POST" action="admin_dash.php" style="display:grid; gap: 16px;">
                    <input type="hidden" name="action" value="update_mechanic_password">
                    <div style="display:grid; gap: 10px;">
                        <label style="font-size: 0.95rem; color: var(--text-muted);">Mechanic</label>
                        <select name="mechanic_id" required style="width:100%; padding: 14px 16px; border-radius: 16px; border: 1px solid rgba(255,255,255,.10); background: rgba(255,255,255,.04); color: #fff;">
                            <option value="">Select mechanic</option>
                            <?php foreach ($mechanic_rows as $mech): ?>
                                <option value="<?= intval($mech['id']) ?>"><?= htmlspecialchars($mech['name']) ?> — <?= htmlspecialchars($mech['email']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:grid; gap: 10px;" class="password-toggle-field">
                        <label style="font-size: 0.95rem; color: var(--text-muted);">New Password</label>
                        <input id="update_password" type="password" name="password" placeholder="Enter new password" required style="width:100%; padding: 14px 16px; border-radius: 16px; border: 1px solid rgba(255,255,255,.10); background: rgba(255,255,255,.04); color: #fff;">
                        <button type="button" class="toggle-password" data-target="update_password" aria-label="Toggle password visibility"><i class='bx bx-show'></i></button>
                    </div>
                    <div style="display:grid; gap: 10px;" class="password-toggle-field">
                        <label style="font-size: 0.95rem; color: var(--text-muted);">Confirm Password</label>
                        <input id="update_confirm_password" type="password" name="confirm_password" placeholder="Confirm new password" required style="width:100%; padding: 14px 16px; border-radius: 16px; border: 1px solid rgba(255,255,255,.10); background: rgba(255,255,255,.04); color: #fff;">
                        <button type="button" class="toggle-password" data-target="update_confirm_password" aria-label="Toggle password visibility"><i class='bx bx-show'></i></button>
                    </div>
                    <button type="submit" class="btn-assign" style="width: fit-content;">Update Mechanic Password</button>
                </form>
            </div>
        </section>

        <!-- Customer Table Card -->
        <div class="table-panel">
            <h2><i class='bx bx-group' style="color: var(--primary-red);"></i> Registered Customer Directory</h2>
            <div style="overflow-x: auto;">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Index</th>
                            <th>Customer Name</th>
                            <th>Email Address</th>
                            <th>Contact Phone</th>
                            <th>Garage Coordinates</th>
                            <th>Joined Log</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customer_rows)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">No registered customer profiles found on standby.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customer_rows as $index => $customer): ?>
                                <tr>
                                    <td style="font-weight: 700; color: var(--text-muted); font-family: monospace;">#<?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></td>
                                    <td><a href="#" class="view-customer" data-id="<?= htmlspecialchars($customer['id']) ?>" style="color: #fff; text-decoration: none;"><strong><?= htmlspecialchars($customer['name']) ?></strong></a></td>
                                    <td><?= htmlspecialchars($customer['email']) ?></td>
                                    <td><?= htmlspecialchars($customer['phone']) ?></td>
                                    <td><?= htmlspecialchars($customer['address'] ?: 'Not Provided') ?></td>
                                    <td><?= htmlspecialchars(date('M j, Y', strtotime($customer['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-panel">
            <h2><i class='bx bx-wrench' style="color: var(--primary-red);"></i> Mechanics Directory</h2>
            <div style="overflow-x: auto;">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Index</th>
                            <th>Mechanic Name</th>
                            <th>Email Address</th>
                            <th>Contact Phone</th>
                            <th>Joined Log</th>
                            <th style="width: 120px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mechanic_rows)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">No mechanics are registered yet.</td>
                            </tr>
                        <?php else: ?>
<?php foreach ($mechanic_rows as $index => $mechanic): ?>
                                <tr>
                                    <td style="font-weight: 700; color: var(--text-muted); font-family: monospace;">#<?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></td>
                                    <td><strong style="color: #fff;"><?= htmlspecialchars($mechanic['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($mechanic['email']) ?></td>
                                    <td><?= htmlspecialchars($mechanic['phone']) ?></td>
                                    <td><?= htmlspecialchars(date('M j, Y', strtotime($mechanic['created_at']))) ?></td>
                                    <td style="text-align:center;">
                                        <form method="POST" action="admin_dash.php" onsubmit="return confirm('Remove this mechanic?');" style="margin:0;">
                                            <input type="hidden" name="action" value="remove_mechanic">
                                            <input type="hidden" name="mechanic_id" value="<?= intval($mechanic['id']) ?>">
                                            <button type="submit" style="background: rgba(255,77,77,0.12); border: 1px solid rgba(255,77,77,0.35); color: #ff4d4d; padding: 10px 14px; border-radius: 12px; font-weight: 800; cursor:pointer;">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel-card" style="margin-bottom: 0;">
            <h2><i class='bx bx-broadcast' style="color: var(--primary-red);"></i> Live Floor Bay Diagnostics Feed</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">Active pit lanes telemetry indicators: <strong>Awaiting job cards initialization sequences...</strong></p>
        </div>
    </main>

    <!-- Details Modal -->
    <div id="detailModal" style="display:none; position: fixed; inset:0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: #0b0b0b; border: 1px solid rgba(255,255,255,0.06); padding: 20px; width: 920px; max-width: 96%; border-radius: 12px; color: #fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                <h3 id="detailTitle" style="margin:0;">Details</h3>
                <button id="detailClose" style="background:transparent; border: none; color: var(--text-muted); font-size: 18px; cursor:pointer;">✕</button>
            </div>
            <div id="detailBody" style="margin-top: 12px; max-height: 60vh; overflow: auto; font-size: 14px; color: var(--text-muted);"></div>
        </div>
    </div>

    <script>
        async function fetchJson(url) {
            const res = await fetch(url, { credentials: 'same-origin' });
            return res.json();
        }

        function showModal(title, html) {
            document.getElementById('detailTitle').textContent = title;
            document.getElementById('detailBody').innerHTML = html;
            document.getElementById('detailModal').style.display = 'flex';
        }
        function hideModal() { document.getElementById('detailModal').style.display = 'none'; }

        document.getElementById('detailClose').addEventListener('click', hideModal);
        document.addEventListener('click', function(e){ if (e.target.matches('.view-customer')) { e.preventDefault(); const id = e.target.dataset.id; fetchJson('api.php?action=customer&id='+encodeURIComponent(id)).then(data=>{
                    if (data.error) return showModal('Error', '<div style="color:#ff6b6b;">'+data.error+'</div>');
                    let html = '<h4 style="margin:0 0 8px 0;">'+(data.customer.name||'')+'</h4>';
                    html += '<div><strong>Contact:</strong> '+(data.customer.phone||'')+' &nbsp; <strong>Email:</strong> '+(data.customer.email||'')+'</div>';
                    html += '<div style="margin-top:8px;"><strong>Address:</strong> '+(data.customer.address||'Not provided')+'</div>';
                    if (data.vehicles && data.vehicles.length) {
                        html += '<hr><h4 style="margin:8px 0;">Vehicles</h4>';
                        html += '<ul>' + data.vehicles.map(v=>'<li>'+v.brand+' '+v.model+' — <a href="#" class="view-vehicle" data-reg="'+v.vehicle_no+'" style="color:var(--neon-info);">'+v.vehicle_no+'</a></li>').join('') + '</ul>';
                    }
                    showModal('Customer Details', html);
            }).catch(err=>showModal('Error', '<div style="color:#ff6b6b;">'+err.message+'</div>')); }});

        document.addEventListener('click', function(e){ if (e.target.matches('.view-vehicle')) { e.preventDefault(); const reg = e.target.dataset.reg; fetchJson('api.php?action=vehicle&reg='+encodeURIComponent(reg)).then(data=>{
                    if (data.error) return showModal('Error', '<div style="color:#ff6b6b;">'+data.error+'</div>');
                    let v = data.vehicle;
                    let html = '<h4 style="margin:0 0 8px 0;">'+(v.brand||'')+' '+(v.model||'')+' — '+(v.vehicle_no||'')+'</h4>';
                    html += '<div><strong>Owner:</strong> '+(v.customer_name||'Unknown')+' &nbsp; <strong>Phone:</strong> '+(v.customer_phone||'')+'</div>';
                    html += '<div style="margin-top:8px;"><strong>Category:</strong> '+(v.category||'')+' &nbsp; <strong>Fuel:</strong> '+(v.fuel_type||'')+'</div>';
                    if (data.history && data.history.length) {
                        html += '<hr><h4 style="margin:8px 0;">Service History</h4>';
                        html += '<ul>' + data.history.map(h=>'<li><strong>'+h.job_card_id+'</strong> — '+h.status+' ('+h.created_at+')'+(h.mechanic_name ? ' — '+h.mechanic_name : '')+'<div style="color:var(--text-muted);">'+(h.repair_notes||'')+'</div></li>').join('') + '</ul>';
                    } else {
                        html += '<div style="margin-top:8px; color:var(--text-muted);">No service history found for this vehicle.</div>';
                    }
                    showModal('Vehicle Details', html);
            }).catch(err=>showModal('Error', '<div style="color:#ff6b6b;">'+err.message+'</div>')); }});

        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', () => {
                const targetId = button.dataset.target;
                const input = document.getElementById(targetId);
                if (!input) return;
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                button.innerHTML = isPassword ? "<i class='bx bx-hide'></i>" : "<i class='bx bx-show'></i>";
            });
        });
    </script>

</body>
</html>