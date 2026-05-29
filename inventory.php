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

// Handle modifying operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_stock') {
        $part_code = trim($_POST['part_code'] ?? '');
        $new_qty = intval($_POST['quantity'] ?? 0);
        $new_price = floatval($_POST['price_per_unit'] ?? 0.0);

        if (!empty($part_code) && $new_qty >= 0 && $new_price >= 0) {
            try {
                $update_stmt = $pdo->prepare("UPDATE inventory SET quantity = ?, price_per_unit = ? WHERE part_code = ?");
                $update_stmt->execute([$new_qty, $new_price, $part_code]);
                $message = "Inventory successfully updated!";
            } catch (PDOException $e) {
                $error = "Update Failed: " . $e->getMessage();
            }
        } else {
            $error = "Please enter valid quantities and pricing details.";
        }
    } elseif ($_POST['action'] === 'add_part') {
        $part_code = trim($_POST['part_code'] ?? '');
        $part_name = trim($_POST['part_name'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $price = floatval($_POST['price_per_unit'] ?? 0.0);
        $low_stock = intval($_POST['low_stock_threshold'] ?? 5);

        if (!empty($part_code) && !empty($part_name) && $quantity >= 0 && $price >= 0 && $low_stock >= 0) {
            try {
                $insert_stmt = $pdo->prepare("INSERT INTO inventory (part_code, part_name, quantity, price_per_unit, low_stock_threshold) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), price_per_unit = VALUES(price_per_unit)");
                $insert_stmt->execute([$part_code, $part_name, $quantity, $price, $low_stock]);
                $message = "Part record added/updated successfully!";
            } catch (PDOException $e) {
                $error = "Operation failed: " . $e->getMessage();
            }
        } else {
            $error = "Please fill all fields with valid non-negative values.";
        }
    } elseif ($_POST['action'] === 'seed_dummy') {
        // Generation of 100+ highly realistic automobile components!
        $categories = ['Oil Filter', 'Brake Pad', 'Spark Plug', 'Clutch Plate', 'Drive Belt', 'Shock Absorber', 'Gasket', 'Wiper Blade', 'Headlight Bulb', 'Engine Oil (1L)', 'Air Filter', 'Brake Shoe', 'Fuel Filter', 'Cabin AC Filter', 'Ignition Coil', 'Radiator Hose', 'Alternator Belt', 'Wheel Bearing'];
        $brands = [
            'HER' => 'Hero',
            'BAJ' => 'Bajaj',
            'HON' => 'Honda',
            'TVS' => 'TVS',
            'ROY' => 'Royal Enfield',
            'YAM' => 'Yamaha',
            'MAR' => 'Maruti Suzuki',
            'HYU' => 'Hyundai',
            'TAT' => 'Tata',
            'MAH' => 'Mahindra',
            'TOY' => 'Toyota'
        ];
        
        $parts_to_add = [];
        // First add the premium hand-curated ones
        $premium_parts = [
            ['HER-FIL-01', 'Hero Splendor Oil Filter', 20, 95.00, 5],
            ['HER-BRK-01', 'Hero Passion Brake Pad', 15, 150.00, 3],
            ['BAJ-FIL-01', 'Bajaj Pulsar Oil Filter', 25, 110.00, 5],
            ['BAJ-CHN-01', 'Bajaj Dominar Chain Kit', 5, 2500.00, 2],
            ['HON-BEL-01', 'Honda Activa Drive Belt', 18, 550.00, 4],
            ['HON-PLG-01', 'Honda Spark Plug (NGK)', 40, 120.00, 10],
            ['TVS-SHK-01', 'TVS Apache Rear Shock', 8, 1200.00, 2],
            ['TVS-BRK-01', 'TVS Jupiter Brake Shoe', 12, 280.00, 4],
            ['RE-OIL-01', 'RE 15W50 Engine Oil', 50, 450.00, 10],
            ['RE-CLU-01', 'RE Classic 350 Clutch Plate', 10, 1800.00, 3],
            ['YAM-AIR-01', 'Yamaha FZ Air Filter', 20, 220.00, 5],
            ['MAR-OIL-01', 'Maruti Swift Oil Filter', 30, 350.00, 5],
            ['MAR-WPR-01', 'Maruti WagonR Wiper Blades', 15, 450.00, 3],
            ['HYU-AIR-01', 'Hyundai i20 AC Filter', 12, 600.00, 3],
            ['TAT-FLT-01', 'Tata Nexon Fuel Filter', 8, 1500.00, 2],
            ['MAH-BRK-01', 'Mahindra Scorpio Brake Pads', 6, 2200.00, 2],
            ['TOY-FIL-01', 'Toyota Innova Cabin Filter', 10, 900.00, 3],
        ];
        
        foreach ($premium_parts as $p) {
            $parts_to_add[] = $p;
        }
        
        // Generate up to 105 total parts
        $count = count($parts_to_add);
        $num_needed = 105 - $count;
        for ($i = 1; $i <= $num_needed; $i++) {
            $brand_code = array_rand($brands);
            $brand_name = $brands[$brand_code];
            $cat = $categories[array_rand($categories)];
            
            $code = $brand_code . '-' . strtoupper(substr(str_replace(' ', '', $cat), 0, 3)) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
            $name = $brand_name . ' ' . $cat;
            $qty = rand(2, 60);
            $price = rand(8, 450) * 10;
            $low = rand(2, 8);
            
            $parts_to_add[] = [$code, $name, $qty, $price, $low];
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO inventory (part_code, part_name, quantity, price_per_unit, low_stock_threshold) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), price_per_unit = VALUES(price_per_unit)");
            foreach ($parts_to_add as $part) {
                $stmt->execute($part);
            }
            $message = "Database successfully seeded with " . count($parts_to_add) . " highly realistic vehicle parts!";
        } catch (PDOException $e) {
            $error = "Bulk population failed: " . $e->getMessage();
        }
    }
}

// Fetch all elements from inventory table
try {
    $stmt = $pdo->query("SELECT * FROM inventory ORDER BY part_code ASC");
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $parts = [];
    $error = "Could not load inventory: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>8099 PitStop | Parts Inventory Command Center</title>
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

        /* Sidebar Navigation matching the elegant F1 racing UI */
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

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .inline-input {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.10);
            color: #fff;
            padding: 12px 16px;
            font-size: 0.95rem;
            border-radius: 14px;
            transition: all 0.2s ease;
        }

        .inline-input:focus {
            outline: none;
            border-color: rgba(225,6,0,.6);
            box-shadow: 0 0 15px rgba(225,6,0,.15);
            background: rgba(255,255,255,.07);
        }

        .btn-update {
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
        }

        .btn-update:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(225, 6, 0, 0.4);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            color: #fff;
            padding: 12px 24px;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.12);
            transform: translateY(-1px);
        }

        /* Status Notifications */
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

        /* Inventory Table CSS */
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
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.01);
        }

        .part-badge {
            font-family: monospace;
            background: rgba(255,255,255,0.08);
            padding: 4px 8px;
            border-radius: 6px;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.06);
            font-weight: 600;
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

        .stock-low {
            color: var(--neon-danger);
            background: rgba(255, 77, 77, 0.12);
            border: 1px solid rgba(255, 77, 77, 0.2);
        }

        .stock-ok {
            color: var(--neon-success);
            background: rgba(52, 211, 153, 0.12);
            border: 1px solid rgba(52, 211, 153, 0.2);
        }

        .inline-edit-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .edit-input {
            background: rgba(13, 21, 39, 0.8);
            border: 1px solid var(--border-glass);
            color: white;
            padding: 8px 12px;
            border-radius: 10px;
            width: 80px;
            text-align: center;
            font-weight: 600;
        }

        .edit-input:focus {
            outline: none;
            border-color: var(--primary-red);
        }

        .btn-mini-save {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            cursor: pointer;
            padding: 8px 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.2s ease;
        }

        .btn-mini-save:hover {
            background: var(--primary-red);
            border-color: var(--primary-red);
            transform: translateY(-1px);
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
            <li class="sidebar-item active"><a href="inventory.php"><i class='bx bx-package'></i> Parts Inventory</a></li>
            <li class="sidebar-item"><a href="job_cards.php"><i class='bx bx-wrench'></i> Live Job Cards</a></li>
        </ul>
        
        <div class="sidebar-logout">
            <a href="logout.php"><i class='bx bx-log-out-circle'></i> Exit System</a>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="header-section">
            <div>
                <h1>Parts Inventory Manager</h1>
                <p style="color: var(--text-muted); margin: 5px 0 0 0;">Maintain formula-level spares efficiency and bulk telemetry configuration</p>
            </div>
            
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="seed_dummy" />
                <button type="submit" class="btn-update" style="background: linear-gradient(135deg, #FF8C00, #E10600);"><i class='bx bx-coin-stack'></i> Seed 100+ Premium Parts</button>
            </form>
        </div>

        <!-- Notification Banner -->
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

        <!-- Quick Add Section -->
        <div class="panel-card">
            <h2><i class='bx bx-plus-circle' style="color: var(--primary-red);"></i> Register New Spare Component</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_part" />
                <div class="form-row">
                    <div class="form-group">
                        <label>Component Code</label>
                        <input type="text" name="part_code" placeholder="e.g. TOY-ROTR-08" required class="inline-input" />
                    </div>
                    <div class="form-group">
                        <label>Part Description</label>
                        <input type="text" name="part_name" placeholder="e.g. Toyota Front Rotors Set" required class="inline-input" />
                    </div>
                    <div class="form-group">
                        <label>Stock Level</label>
                        <input type="number" name="quantity" placeholder="Units in stock" min="0" required class="inline-input" />
                    </div>
                    <div class="form-group">
                        <label>Price (₹)</label>
                        <input type="number" step="0.01" name="price_per_unit" placeholder="Price per unit" min="0" required class="inline-input" />
                    </div>
                    <div class="form-group">
                        <label>Min Threshold</label>
                        <input type="number" name="low_stock_threshold" value="5" min="0" required class="inline-input" />
                    </div>
                </div>
                <button type="submit" class="btn-update"><i class='bx bx-save'></i> Register Spare Component</button>
            </form>
        </div>

        <!-- Inventory List View -->
        <div class="panel-card" style="margin-bottom: 0;">
            <h2><i class='bx bx-list-ul' style="color: var(--primary-red);"></i> Active Stock telemetry (<?= count($parts) ?> components logged)</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Part Code</th>
                            <th>Component Description</th>
                            <th>Stock Count</th>
                            <th>Status Status</th>
                            <th>Telemetry Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parts)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">No inventory records detected. Click the "Seed 100+ Premium Parts" button at the top to auto-populate instantly.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($parts as $item): ?>
                                <?php $is_low = ($item['quantity'] <= $item['low_stock_threshold']); ?>
                                <tr>
                                    <td><span class="part-badge"><?= htmlspecialchars($item['part_code']) ?></span></td>
                                    <td style="font-weight: 600;"><?= htmlspecialchars($item['part_name']) ?></td>
                                    <td><?= $item['quantity'] ?> Units</td>
                                    <td>
                                        <span class="status-pill <?= $is_low ? 'stock-low' : 'stock-ok' ?>">
                                            <i class='bx <?= $is_low ? 'bx-error-circle' : 'bx-check-double' ?>'></i>
                                            <?= $is_low ? 'Low Stock' : 'Good Stock' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="inline-edit-form">
                                            <input type="hidden" name="action" value="update_stock">
                                            <input type="hidden" name="part_code" value="<?= htmlspecialchars($item['part_code']) ?>">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <div style="display: flex; flex-direction: column; align-items: center; gap: 2px;">
                                                    <span style="font-size: 8px; text-transform: uppercase; color: var(--text-muted);">Qty</span>
                                                    <input type="number" name="quantity" class="edit-input" value="<?= $item['quantity'] ?>" min="0">
                                                </div>
                                                <div style="display: flex; flex-direction: column; align-items: center; gap: 2px;">
                                                    <span style="font-size: 8px; text-transform: uppercase; color: var(--text-muted);">Price (₹)</span>
                                                    <input type="number" step="0.01" name="price_per_unit" class="edit-input" style="width: 100px;" value="<?= $item['price_per_unit'] ?>" min="0">
                                                </div>
                                                <button type="submit" class="btn-mini-save"><i class='bx bx-save'></i> Save</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>