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

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'];
    if ($redirect_target && $role === 'CUSTOMER') {
        header('Location: ' . $redirect_target);
    } else {
        header('Location: ' . strtolower($role) . '_dash.php');
    }
    exit;
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error_message = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            if ($redirect_target && $user['role'] === 'CUSTOMER') {
                header('Location: ' . $redirect_target);
            } else {
                switch ($user['role']) {
                    case 'ADMIN':
                        header('Location: admin_dash.php');
                        break;
                    case 'MECHANIC':
                        header('Location: mechanic_dash.php');
                        break;
                    default:
                        header('Location: customer_dash.php');
                        break;
                }
            }
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>8099 PitStop | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/racing-ui.css">
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
        .login-shell {
            width: min(480px, calc(100% - 40px));
            margin: 0 auto;
            padding: 48px 32px;
        }
        .login-card {
            background: rgba(15, 20, 30, 0.95);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 28px;
            box-shadow: 0 24px 80px rgba(0,0,0,.35);
            padding: 36px 32px;
        }
        .login-card h1 {
            margin-top: 0;
            font-size: 2.2rem;
            letter-spacing: 0.02em;
        }
        .login-card p {
            color: rgba(255,255,255,.72);
            margin-bottom: 28px;
        }
        .login-field {
            display: grid;
            gap: 8px;
            margin-bottom: 20px;
        }
        .login-field label {
            font-size: 0.95rem;
            color: rgba(255,255,255,.78);
        }
        .login-field input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.04);
            color: #fff;
            font-size: 1rem;
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
        }
        .alert {
            padding: 16px 18px;
            border-radius: 16px;
            margin-bottom: 22px;
            background: rgba(225,6,0,.12);
            border: 1px solid rgba(225,6,0,.24);
            color: #ffd7d7;
        }
        .login-footer {
            margin-top: 24px;
            color: rgba(255,255,255,.65);
            text-align: center;
        }
        .login-footer a {
            color: #ff7c7c;
            text-decoration: none;
        }
    </style>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-shell">
        <div style="display:flex; justify-content:center; margin-bottom:28px;">
            <img src="includes/image/8099LO.png" alt="8099 PitStop" style="height: 120px; width: auto; max-width: 280px;">
        </div>
        <div class="login-card">
            <h1>Welcome Back</h1>
            <p>Sign in to access the admin, mechanic, or customer dashboard for 8099 PitStop.</p>
            <?php if ($error_message): ?>
                <div class="alert"><i class='bx bx-error-circle'></i> <?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            <form action="" method="POST">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect_target) ?>">
                <div class="login-field">
                    <label for="email">Email Address</label>
                    <input id="email" name="email" type="email" placeholder="admin@pitstop.com" required>
                </div>
                <div class="login-field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="submit-btn"><i class='bx bx-log-in'></i> Login Securely</button>
            </form>
            <div class="login-footer">
                <p>Don't have an account? <a href="register.php?redirect=<?= urlencode($redirect_target) ?>">Create one here</a></p>
                <p>Forgot your password? <a href="forgot_password.php?redirect=<?= urlencode($redirect_target) ?>">Reset it here</a></p>
                <p style="margin-top: 10px; font-size: 0.85rem; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 12px;">
                    Are you an active technician? <a href="mechanic_login.php" style="color: #fbbf24; font-weight: 600;"><i class='bx bx-wrench'></i> Staff Pit Lane Terminal</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>