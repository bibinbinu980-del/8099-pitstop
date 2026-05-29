<?php
session_start();
require_once 'config/db.php';

$redirect_target = '';

function normalize_redirect($value) {
    $allowed = ['customer_dash.php', 'admin_dash.php', 'mechanic_dash.php'];
    $value = trim($value);
    $candidate = basename($value);
    return in_array($candidate, $allowed, true) ? $candidate : '';
}

if (!empty($_GET['redirect'])) {
    $redirect_target = normalize_redirect($_GET['redirect']);
}

if (!empty($_POST['redirect'])) {
    $redirect_target = normalize_redirect($_POST['redirect']);
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'];
    header('Location: ' . strtolower($role) . '_dash.php');
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error_message = 'Please fill out all fields.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            // Check if email or phone already exists
            $check_stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR phone = ?');
            $check_stmt->execute([$email, $phone]);

            if ($check_stmt->fetch()) {
                $error_message = 'Email or phone number already registered.';
            } else {
                // Create new customer account
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $insert_stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)');
                $insert_stmt->execute([$name, $email, $phone, $hashed_password, 'CUSTOMER']);

                $success_message = 'Account created successfully! Please login with your credentials.';
                // Clear form
                $name = $email = $phone = $password = $confirm_password = '';
            }
        } catch (PDOException $e) {
            $error_message = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>8099 PitStop | Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/racing-ui.css?v=1">
    <link rel="icon" href="includes/image/8099LO.png" type="image/png">
    <link rel="license" href="LICENSE">
    <meta name="author" content="Bibin Binu">
    <meta name="copyright" content="Copyright (c) <?= date('Y') ?> Bibin Binu">
    <meta name="license" content="MIT License">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top, rgba(225,6,0,.18), transparent 20%),
                        linear-gradient(180deg, #070708 0%, #0f1119 100%);
            color: #f8fafc;
        }
        .register-shell {
            width: min(480px, calc(100% - 40px));
            margin: 0 auto;
            padding: 48px 32px;
        }
        .register-card {
            background: rgba(15, 20, 30, 0.95);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 28px;
            box-shadow: 0 24px 80px rgba(0,0,0,.35);
            padding: 36px 32px;
        }
        .register-card h1 {
            margin-top: 0;
            font-size: 2.2rem;
            letter-spacing: 0.02em;
        }
        .register-card p {
            color: rgba(255,255,255,.72);
            margin-bottom: 28px;
        }
        .register-field {
            display: grid;
            gap: 8px;
            margin-bottom: 20px;
        }
        .register-field label {
            font-size: 0.95rem;
            color: rgba(255,255,255,.78);
        }
        .register-field input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.04);
            color: #fff;
            font-size: 1rem;
        }
        .register-field input:focus {
            outline: none;
            border-color: rgba(225, 6, 0, 0.5);
            background: rgba(255,255,255,.06);
        }
        .submit-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            border-radius: 18px;
            border: none;
            background: linear-gradient(135deg, #e10600, #ff5a35);
            color: white;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            font-weight: 600;
        }
        .submit-btn:hover {
            opacity: 0.9;
        }
        .alert {
            padding: 16px 18px;
            border-radius: 16px;
            margin-bottom: 22px;
            font-size: 0.95rem;
        }
        .alert-error {
            background: rgba(225,6,0,.12);
            border: 1px solid rgba(225,6,0,.24);
            color: #ffd7d7;
        }
        .alert-success {
            background: rgba(52,211,153,.12);
            border: 1px solid rgba(52,211,153,.24);
            color: #a7f3d0;
        }
        .register-footer {
            margin-top: 24px;
            color: rgba(255,255,255,.65);
            text-align: center;
        }
        .register-footer a {
            color: #ff7c7c;
            text-decoration: none;
            font-weight: 600;
        }
        .register-footer a:hover {
            text-decoration: underline;
        }
    </style>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="register-shell">
        <div style="display:flex; justify-content:center; margin-bottom:28px;">
            <img src="includes/image/8099LO.png" alt="8099 PitStop" style="height: 120px; width: auto; max-width: 280px;">
        </div>
        <div class="register-card">
            <h1>Create Account</h1>
            <p>Join 8099 PitStop and manage your vehicle services with ease.</p>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error"><i class='bx bx-error-circle'></i> <?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><i class='bx bx-check-circle'></i> <?= htmlspecialchars($success_message) ?></div>
                <div style="margin-top: 20px; text-align: center;">
                    <a href="login.php<?= $redirect_target ? '?redirect=' . urlencode($redirect_target) : '' ?>" style="color: #ff7c7c; text-decoration: none; font-weight: 600;">Go to Login</a>
                </div>
            <?php else: ?>
                <form method="POST" action="register.php<?= $redirect_target ? '?redirect=' . urlencode($redirect_target) : '' ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect_target) ?>">
                    <div class="register-field">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required>
                    </div>

                    <div class="register-field">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
                    </div>

                    <div class="register-field">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>" required>
                    </div>

                    <div class="register-field">
                        <label for="password">Password (min 6 characters)</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="register-field">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class='bx bx-user-plus'></i> Create Account
                    </button>
                </form>

                <div class="register-footer">
                    Already have an account? <a href="login.php<?= $redirect_target ? '?redirect=' . urlencode($redirect_target) : '' ?>">Login here</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
