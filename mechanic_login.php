<?php
session_start();
require_once 'config/db.php';

// If already logged in as a mechanic, redirect directly to the dashboard
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'MECHANIC') {
    header('Location: mechanic_dash.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error_message = 'Please enter both email and password credentials.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['role'] !== 'MECHANIC') {
                    $error_message = 'Access Denied: This terminal is strictly reserved for Authorized mechanics only.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    header('Location: mechanic_dash.php');
                    exit;
                }
            } else {
                $error_message = 'Invalid staff email or password. Please verify and retry.';
            }
        } catch (PDOException $e) {
            $error_message = 'Connection error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>8099 PitStop | Mechanic Pit Lane Terminal</title>
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
            background: radial-gradient(circle at top, rgba(251, 191, 36, 0.1), transparent 25%),
                        linear-gradient(180deg, #060607 0%, #0d0e12 100%);
            color: #f8fafc;
            display: grid;
            place-items: center;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: repeating-linear-gradient(135deg, rgba(255,255,255,.01) 0 8px, transparent 8px 16px),
                              repeating-linear-gradient(45deg, rgba(255,255,255,.01) 0 4px, transparent 4px 8px);
            opacity: 0.1;
            pointer-events: none;
        }

        .login-shell {
            width: min(480px, calc(100% - 40px));
            padding: 40px 0;
        }

        .login-card {
            background: rgba(18, 18, 18, 0.85);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 28px;
            box-shadow: 0 30px 90px rgba(0,0,0,.45);
            padding: 40px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(20px);
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #fbbf24, #d97706);
        }

        .login-card h1 {
            margin-top: 0;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-card p {
            color: rgba(255,255,255,.7);
            font-size: 0.95rem;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 22px;
        }

        .form-group label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,0.6);
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.10);
            background: rgba(255,255,255,.04);
            color: #fff;
            font-size: 0.98rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #fbbf24;
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.15);
            background: rgba(255,255,255,.07);
        }

        .submit-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 24px;
            border-radius: 14px;
            border: none;
            background: linear-gradient(135deg, #fbbf24, #d97706);
            color: #000;
            font-size: 0.98rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 4px 20px rgba(251, 191, 36, 0.25);
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(251, 191, 36, 0.4);
        }

        .alert {
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-footer {
            margin-top: 28px;
            color: rgba(255,255,255,.5);
            text-align: center;
            font-size: 0.88rem;
            border-top: 1px solid rgba(255,255,255,0.06);
            padding-top: 20px;
        }

        .login-footer a {
            color: #fbbf24;
            text-decoration: none;
            font-weight: 600;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-shell">
        <div style="display:flex; justify-content:center; margin-bottom:28px;">
            <a href="index.php">
                <img src="includes/image/8099LO.png" alt="8099 PitStop" style="height: 70px; width: auto;">
            </a>
        </div>
        
        <div class="login-card">
            <h1><i class='bx bx-wrench' style="color: #fbbf24;"></i> Staff Pit Lane</h1>
            <p>Technician credentials authorization interface. Verify security clearance to access active bay diagnostic streams.</p>
            
            <?php if ($error_message): ?>
                <div class="alert">
                    <i class='bx bx-error-circle' style="font-size: 20px;"></i>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="form-group">
                    <label for="email">Technician Secure Email</label>
                    <input id="email" name="email" type="email" class="form-control" placeholder="mechanic@pitstop.com" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Security Clearance Key</label>
                    <input id="password" name="password" type="password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class='bx bx-key'></i> Authorize Technician Terminal
                </button>
            </form>
            
            <div class="login-footer">
                <p>Not a mechanic? <a href="login.php">Customer / Admin Terminal</a></p>
            </div>
        </div>
    </div>
</body>
</html>
