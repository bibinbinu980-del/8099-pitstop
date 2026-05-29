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

$error_message = '';
$success_message = '';
$show_reset_form = false;

if (!empty($_SESSION['password_reset_user_id'])) {
    $show_reset_form = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? 'request';

    if ($action === 'request') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');

        if ($email === '' || $phone === '') {
            $error_message = 'Please enter both your registered email and phone number.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND phone = ? LIMIT 1');
            $stmt->execute([$email, $phone]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION['password_reset_user_id'] = $user['id'];
                $_SESSION['password_reset_email'] = $email;
                $show_reset_form = true;
            } else {
                $error_message = 'No matching account was found with that email and phone combination.';
            }
        }
    } elseif ($action === 'reset') {
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        $user_id = $_SESSION['password_reset_user_id'] ?? null;

        if (!$user_id) {
            $error_message = 'Unable to complete password reset. Please start again.';
        } elseif ($password === '' || $confirm_password === '') {
            $error_message = 'Please enter and confirm your new password.';
            $show_reset_form = true;
        } elseif (strlen($password) < 6) {
            $error_message = 'Password must be at least 6 characters long.';
            $show_reset_form = true;
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
            $show_reset_form = true;
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $update_stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $update_stmt->execute([$hashed_password, $user_id]);

            unset($_SESSION['password_reset_user_id'], $_SESSION['password_reset_email']);
            $success_message = 'Your password has been updated successfully. You may now log in with your new password.';
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>8099 PitStop | Reset Password</title>
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
        .reset-shell {
            width: min(520px, calc(100% - 40px));
            margin: 0 auto;
            padding: 48px 32px;
        }
        .reset-card {
            background: rgba(15, 20, 30, 0.95);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 28px;
            box-shadow: 0 24px 80px rgba(0,0,0,.35);
            padding: 36px 32px;
        }
        .reset-card h1 {
            margin-top: 0;
            font-size: 2.2rem;
            letter-spacing: 0.02em;
        }
        .reset-card p {
            color: rgba(255,255,255,.72);
            margin-bottom: 28px;
        }
        .reset-field {
            display: grid;
            gap: 8px;
            margin-bottom: 20px;
        }
        .reset-field label {
            font-size: 0.95rem;
            color: rgba(255,255,255,.78);
        }
        .reset-field input {
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
            font-weight: 600;
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
        .reset-footer {
            margin-top: 24px;
            color: rgba(255,255,255,.65);
            text-align: center;
        }
        .reset-footer a {
            color: #ff7c7c;
            text-decoration: none;
            font-weight: 600;
        }
        .reset-footer a:hover {
            text-decoration: underline;
        }
    </style>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="reset-shell">
        <div style="display:flex; justify-content:center; margin-bottom:28px;">
            <img src="includes/image/8099LO.png" alt="8099 PitStop" style="height: 120px; width: auto; max-width: 280px;">
        </div>
        <div class="reset-card">
            <h1>Reset Your Password</h1>
            <p>Enter your registered details below so you can safely update your account password.</p>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error"><i class='bx bx-error-circle'></i> <?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><i class='bx bx-check-circle'></i> <?= htmlspecialchars($success_message) ?></div>
                <div class="reset-footer">
                    <a href="login.php<?= $redirect_target ? '?redirect=' . urlencode($redirect_target) : '' ?>">Return to Login</a>
                </div>
            <?php else: ?>
                <?php if ($show_reset_form): ?>
                    <form method="POST" action="forgot_password.php<?= $redirect_target ? '?redirect=' . urlencode($redirect_target) : '' ?>">
                        <input type="hidden" name="form_action" value="reset">
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect_target) ?>">
                        <div class="reset-field">
                            <label for="password">New Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter new password" required>
                        </div>
                        <div class="reset-field">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password" required>
                        </div>
                        <button type="submit" class="submit-btn"><i class='bx bx-key'></i> Save New Password</button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="forgot_password.php<?= $redirect_target ? '?redirect=' . urlencode($redirect_target) : '' ?>">
                        <input type="hidden" name="form_action" value="request">
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect_target) ?>">
                        <div class="reset-field">
                            <label for="email">Registered Email</label>
                            <input type="email" id="email" name="email" placeholder="you@example.com" required>
                        </div>
                        <div class="reset-field">
                            <label for="phone">Registered Phone</label>
                            <input type="tel" id="phone" name="phone" placeholder="9999999999" required>
                        </div>
                        <button type="submit" class="submit-btn"><i class='bx bx-mail-send'></i> Verify Account</button>
                    </form>
                <?php endif; ?>

                <div class="reset-footer">
                    <a href="login.php<?= $redirect_target ? '?redirect=' . urlencode($redirect_target) : '' ?>">Back to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
