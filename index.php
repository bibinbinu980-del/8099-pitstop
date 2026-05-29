<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>8099 PitStop | Premium Automotive Command Suite</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/racing-ui.css?v=1">
    <link rel="icon" href="includes/image/8099LO.png" type="image/png">
    <link rel="license" href="LICENSE">
    <meta name="author" content="Bibin Binu">
    <meta name="copyright" content="Copyright (c) <?= date('Y') ?> Bibin Binu">
    <meta name="license" content="MIT License">
    <style>
        :root {
            --primary-red: #E10600;
            --bg-dark: #0D0D0D;
            --card-dark: #1A1A1A;
            --silver: #C0C0C0;
            --text-white: #F5F5F5;
            --muted: rgba(245, 245, 245, 0.72);
            --glass: rgba(26, 26, 26, 0.76);
            --border-glass: rgba(255, 255, 255, 0.10);
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

        .main-wrapper {
            width: min(1280px, calc(100% - 40px));
            margin: 0 auto;
            padding: 32px 0 64px;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 22px 28px;
            margin-bottom: 32px;
            background: rgba(13,13,13,.82);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 24px;
            backdrop-filter: blur(18px);
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: var(--text-white);
        }

        .nav-logo-img {
            max-height: 55px;
            width: auto;
            display: block;
            object-fit: contain;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 26px;
            flex-wrap: wrap;
        }

        .nav-link {
            color: var(--muted);
            text-decoration: none;
            font-weight: 500;
            transition: color .2s ease;
        }

        .nav-link:hover { color: #fff; }

        .nav-actions { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }

        .btn-ghost,
        .btn-neon { text-decoration: none; }

        .btn-ghost {
            padding: 12px 22px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.04);
            color: var(--text-white);
            font-weight: 600;
            transition: transform .2s ease, background .2s ease;
        }

        .btn-ghost:hover {
            transform: translateY(-1px);
            background: rgba(225,6,0,.1);
            border-color: rgba(225,6,0,.35);
        }

        .btn-neon {
            padding: 12px 22px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary-red), #ff2a24);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 4px 20px rgba(225, 6, 0, 0.3);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .btn-neon:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(225, 6, 0, 0.45);
        }

        .section { padding: 28px 0; }

        .hero {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 32px;
            align-items: center;
        }

        .hero-copy small {
            color: var(--primary-red);
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .hero-copy h1 {
            font-size: clamp(3rem, 3.8vw, 4.8rem);
            line-height: 0.98;
            margin: 0 0 20px;
        }

        .hero-copy p {
            max-width: 620px;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .hero-ctas { display: flex; flex-wrap: wrap; gap: 16px; }

        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 24px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.04);
            color: #fff;
            font-weight: 700;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .hero-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 24px 56px rgba(225,6,0,.18);
        }

        .hero-visual { position: relative; min-height: 580px; }

        .hero-panel {
            position: absolute;
            inset: 0;
            border-radius: 32px;
            background: linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,0));
            border: 1px solid rgba(255,255,255,.08);
            box-shadow: 0 32px 90px rgba(0,0,0,.36);
            overflow: hidden;
            backdrop-filter: blur(22px);
        }

        .hero-glow {
            position: absolute;
            top: -18%;
            right: -18%;
            width: 520px;
            height: 520px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(225,6,0,.18), transparent 55%);
            filter: blur(42px);
            pointer-events: none;
        }

        .hero-grid {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 22px;
            width: 100%;
            height: 100%;
            padding: 24px;
        }

        .racing-card {
            background: linear-gradient(180deg, rgba(16,16,16,.95), rgba(22,22,22,.95));
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 28px;
            overflow: hidden;
            position: relative;
            min-height: 230px;
            box-shadow: 0 18px 40px rgba(0,0,0,.25);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 24px;
        }

        .racing-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,.01);
            pointer-events: none;
        }

        .card-head small {
            color: var(--primary-red);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            font-size: .8rem;
        }

        .card-head h2 {
            margin: 16px 0 0;
            font-size: 1.5rem;
            line-height: 1.1;
        }

        .card-foot {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.10);
            color: var(--text-white);
            font-size: .82rem;
            font-weight: 700;
        }

        .hero-art {
            position: relative;
            min-height: 450px;
            background: radial-gradient(circle at top, rgba(225,6,0,.18), transparent 28%),
                        radial-gradient(circle at 88% 10%, rgba(255,255,255,.12), transparent 18%),
                        linear-gradient(180deg, rgba(12,12,12,.98), rgba(6,6,6,.96));
            border-radius: 26px;
            overflow: hidden;
            display: grid;
            place-items: center;
            padding: 28px;
            border: 1px solid rgba(255,255,255,.08);
        }

        .hero-art::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500"><path fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="4" d="M62 384c18-30 42-62 78-76s116-22 168-22 138 4 190 24 90 62 110 94 20 66 8 90-34 44-64 50-102 10-154 10-130-2-170-14-80-32-98-66-20-72 0-90z"/><path fill="none" stroke="rgba(225,6,0,0.14)" stroke-width="4" d="M128 174c28-24 64-40 102-42s76 4 106 20 58 50 72 80 16 78 8 100-22 58-48 70-68 24-108 26-98-2-130-16-68-36-84-64-16-68-4-90 18-38 44-54z"/></svg>');
            background-size: cover;
            background-position: center;
            opacity: 0.22;
        }

        .vehicle-card {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 24px;
            padding: 22px;
            width: min(100%, 480px);
            display: grid;
            gap: 18px;
            z-index: 2;
        }

        .vehicle-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
        }

        .vehicle-top strong { font-size: 1.55rem; }
        .vehicle-top span { color: var(--muted); }

        .track-grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 22px; margin-top: 28px; }

        .track-widget {
            position: relative;
            padding: 26px;
            border-radius: 26px;
            background: var(--glass);
            border: 1px solid rgba(255,255,255,.08);
            overflow: hidden;
        }

        .track-widget::before {
            content: '';
            position: absolute;
            top: -28px;
            right: -28px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(225,6,0,.12);
            pointer-events: none;
        }

        .track-widget .label { text-transform: uppercase; letter-spacing: 1.6px; color: var(--primary-red); font-size: .78rem; font-weight: 700; }
        .track-widget .value { margin: 18px 0 0; font-size: 2.75rem; font-weight: 800; line-height: 1; }
        .track-widget .subtext { margin-top: 12px; color: var(--muted); font-size: .98rem; }

        .dashboard { display: grid; grid-template-columns: minmax(0,1.6fr) minmax(0,1fr); gap: 26px; margin-bottom: 32px; }

        .overview-panel, .feature-panel { background: var(--glass); border: 1px solid rgba(255,255,255,.08); border-radius: 28px; padding: 30px; }

        .overview-panel h3, .feature-panel h3 { font-size: 1.45rem; margin: 0 0 16px; }

        .booking-card { display: grid; gap: 20px; }
        .field-group { display: grid; gap: 10px; }
        .field-group label { font-size: .82rem; letter-spacing: .2em; color: var(--muted); text-transform: uppercase; }

        input, select { background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.12); border-radius: 18px; color: #fff; padding: 14px 16px; font-size: .96rem; }
        input:focus, select:focus { outline: none; border-color: rgba(225,6,0,.55); box-shadow: 0 0 18px rgba(225,6,0,.14); }
        select option { background-color: #1a1a1a; color: #ffffff; }
        .row { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 18px; }
        .booking-card button { width: 100%; }

        .status-progress, .status-ready, .status-alert { display:inline-flex; align-items:center; justify-content:center; border-radius:999px; padding:10px 16px; font-size:.88rem; font-weight:700; }
        .status-progress { background: linear-gradient(90deg, rgba(225,6,0,.18), rgba(255,255,255,.04)); border: 1px solid rgba(225,6,0,.25); }
        .status-ready { background: linear-gradient(90deg, rgba(16,185,129,.2), rgba(255,255,255,.04)); border:1px solid rgba(16,185,129,.22); }
        .status-alert { background: linear-gradient(90deg, rgba(249,115,22,.18), rgba(255,255,255,.04)); border:1px solid rgba(249,115,22,.22); }

        .grid-cards { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 24px; margin-bottom: 32px; }

        .grid-cards .vehicle-card { background: linear-gradient(180deg, rgba(18,18,18,.96), rgba(12,12,12,.98)); border: 1px solid rgba(255,255,255,.08); border-radius: 28px; padding: 26px; position: relative; overflow: hidden; }
        .grid-cards .vehicle-card::before { content: ''; position: absolute; top: -10px; right: -10px; width: 120px; height: 120px; border-radius: 50%; background: rgba(225,6,0,.08); }
        .grid-cards h4 { margin: 0 0 10px; font-size: 1.18rem; }
        .grid-cards small { color: var(--muted); }
        .grid-cards p { color: var(--muted); margin: 16px 0 0; }
        .stat-line { display:flex; justify-content:space-between; gap:12px; margin-top:20px; font-size:.95rem; }
        .stat-line strong { color: #fff; }

        .panel-grid { display: grid; grid-template-columns: 1.2fr .8fr; gap: 26px; margin-bottom: 32px; }

        .team-panel, .review-panel, .pricing-panel { background: var(--glass); border: 1px solid rgba(255,255,255,.08); border-radius: 28px; padding: 30px; }
        .panel-title { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom: 22px; }
        .panel-title h3 { margin:0; font-size:1.4rem; }
        .panel-title small { color: var(--muted); text-transform:uppercase; letter-spacing:1.5px; }

        .mechanic-list { display:grid; gap:18px; }
        .mechanic-card { display:flex; justify-content:space-between; align-items:center; gap:18px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 22px; padding: 20px; }
        .mechanic-card strong { font-size: 1rem; }
        .capacity { color: var(--muted); font-size: .95rem; gap: 10px; display: inline-flex; align-items: center; }

        .review-grid { display:grid; gap:18px; }
        .review-card { background: rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08); border-radius: 26px; padding: 26px; min-height: 170px; display:grid; gap:14px; }
        .review-card strong { font-size: 1rem; }
        .review-card p { color: var(--muted); line-height:1.75; }
        .stars { display:flex; gap:6px; color:#facc15; }

        .pricing-grid { display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 22px; }
        .pricing-card {
            position: relative;
            overflow: hidden;
            border-radius: 30px;
            padding: 32px;
            background: linear-gradient(180deg, rgba(18,18,18,.98), rgba(15,15,15,.98));
            border: 1px solid rgba(255,255,255,.08);
            box-shadow: 0 18px 50px rgba(0,0,0,.28);
            display: flex;
            flex-direction: column;
            min-height: 360px;
        }
        .pricing-card::before { content:''; position:absolute; top:-36px; left:-36px; width:110px; height:110px; border-radius:50%; background: rgba(225,6,0,.12); }
        .pricing-card h4 { margin:0 0 12px; font-size:1.2rem; }
        .pricing-card .price { margin: 6px 0 18px; font-size:2.4rem; font-weight:800; }
        .pricing-card ul { list-style:none; padding:0; margin:0; display:grid; gap:12px; flex: 1 1 auto; }
        .pricing-card li { color: var(--muted); font-size:.95rem; }
        .pricing-card .cta { margin-top: 20px; width:100%; align-self: stretch; }

        .footer { margin-top:40px; padding-top:24px; border-top: 1px solid rgba(255,255,255,.08); color: var(--muted); text-align:center; font-size:.95rem; }

        @media (max-width: 1120px) {
            .hero { grid-template-columns: 1fr; }
            .track-grid, .grid-cards, .panel-grid, .pricing-grid { grid-template-columns: 1fr; }
            .dashboard { grid-template-columns: 1fr; }
        }

        @media (max-width: 760px) {
            .navbar { flex-direction: column; align-items: flex-start; padding: 20px; }
            .nav-links { gap: 12px; }
            .hero-copy h1 { font-size: 2.6rem; }
            .hero-visual { min-height: 420px; }
        }

        .btn-primary { border-radius: 16px; border: 1px solid rgba(225,6,0,.35); background-size: 300% 300%; background-image: linear-gradient(90deg, rgba(225,6,0,1), rgba(225,6,0,.10), rgba(56,189,248,.18)); animation: rg 3.6s ease-in-out infinite; color: #fff; padding: 14px 24px; font-size: 1rem; cursor:pointer; }
        .btn-primary:hover { transform: translateY(-2px) scale(1.01); box-shadow: 0 0 0 1px rgba(225,6,0,.25), 0 0 22px rgba(225,6,0,.22), 0 0 62px rgba(225,6,0,.12); }
        .cta { width: 100%; }
        .booking-note { color: var(--muted); line-height: 1.8; margin-bottom: 22px; }
        .feature-list { display: grid; gap: 14px; margin-bottom: 24px; }
        .feature-list div { display: flex; align-items: center; gap: 12px; padding: 16px 18px; border-radius: 18px; border: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.03); color: var(--muted); font-size: 0.96rem; }
        .feature-list i { color: #ff6b6b; font-size: 1.1rem; }
        .cta-buttons { display: flex; flex-wrap: wrap; gap: 14px; }

        @keyframes rg { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; }}
    </style>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-wrapper">
        <header class="navbar">
            <a class="nav-logo" href="index.php" aria-label="8099 PitStop">
                <img src="includes/image/8099LO.png" alt="8099 PitStop" style="height: 200px; width: auto;">
            </a>
            <nav class="nav-links">
                <a class="nav-link" href="#booking"><i class='bx bx-calendar' style="vertical-align: middle; margin-right: 4px;"></i> Schedule Pit Stop</a>
                <a class="nav-link" href="#mechanics"><i class='bx bx-user-voice' style="vertical-align: middle; margin-right: 4px;"></i> Elite Team</a>
                <a class="nav-link" href="#pricing"><i class='bx bx-purchase-tag' style="vertical-align: middle; margin-right: 4px;"></i> Service Pricing</a>
            </nav>
            <div class="nav-actions">
                <a class="btn-ghost" href="mechanic_login.php" style="border-color: rgba(251, 191, 36, 0.4); color: #fbbf24; display: inline-flex; align-items: center; gap: 6px;"><i class='bx bx-wrench'></i> Staff Pit Lane</a>
                <a class="btn-ghost" href="login.php"><i class='bx bx-log-in'></i> Login</a>
                <a class="btn-neon" href="login.php"><i class='bx bx-tachometer'></i> Launch Dashboard</a>
            </div>
        </header>

        <section class="hero section">
            <div class="hero-copy">
                <small>Premium Garage Command</small>
                <h1>Fast. Futuristic. Formula 1 inspired workshop control.</h1>
                <p>8099 PitStop blends luxury service management with high-velocity garage operations. Monitor bookings, dispatch mechanics, manage vehicles, and keep every repair moving like a championship pit stop.</p>
                <div class="hero-ctas">
                    <a class="btn-neon" href="login.php" style="display: inline-flex; align-items: center; gap: 10px;"><i class='bx bx-tachometer'></i>Launch Service Dashboard</a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-panel"></div>
                <div class="hero-glow"></div>
                <div class="hero-grid">
                    <div class="racing-card neon-hover">
                        <div class="card-head">
                            <small>Supercar Response</small>
                            <h2>Elite Track Repair</h2>
                        </div>
                        <div class="card-foot">
                            <span class="chip">GT-Ready</span>
                            <span class="chip">3 min dispatch</span>
                        </div>
                    </div>
                    <div class="racing-card neon-hover">
                        <div class="card-head">
                            <small>Bike Service</small>
                            <h2>Supersport Tune-Up</h2>
                        </div>
                        <div class="card-foot">
                            <span class="chip">Precision Jet</span>
                            <span class="chip">Expert mechanic</span>
                        </div>
                    </div>
                    <div class="racing-card neon-hover">
                        <div class="card-head">
                            <small>Performance Suite</small>
                            <h2>Live Vehicle Pulse</h2>
                        </div>
                        <div class="card-foot">
                            <span class="chip">Data telemetry</span>
                            <span class="chip">Carbon design</span>
                        </div>
                    </div>
                    <div class="hero-art neon-hover">
                        <div class="vehicle-card">
                            <div class="vehicle-top">
                                <div>
                                    <strong>V12 Hyper GT</strong>
                                    <span>Launch control diagnostics</span>
                                </div>
                                <span class="chip">Active</span>
                            </div>
                            <div class="vehicle-top">
                                <span>Fuel level</span>
                                <strong>82%</strong>
                            </div>
                            <div class="vehicle-top">
                                <span>Track ETA</span>
                                <strong>18 min</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="track-grid section">
            <div class="track-widget reveal">
                <div class="label">Service Queue</div>
                <div class="value">24 Active</div>
                <div class="subtext">Optimized dispatch across pit floor.</div>
            </div>
            <div class="track-widget reveal">
                <div class="label">Mechanics Online</div>
                <div class="value">8 Professionals</div>
                <div class="subtext">Available for rapid allocation.</div>
            </div>
            <div class="track-widget reveal">
                <div class="label">Customer Satisfaction</div>
                <div class="value">4.9 / 5.0</div>
                <div class="subtext">Trust score from premium clients.</div>
            </div>
        </section>

        <section id="booking" class="dashboard section reveal">
            <div class="overview-panel">
                <div class="panel-title">
                    <div>
                        <h3>Service Booking Dashboard</h3>
                        <small>Real-time service management</small>
                    </div>
                </div>
                        <div class="booking-card">
                        <p class="booking-note">Access the full customer portal to book services, track repair status, manage your vehicles, and view invoices in one secure place.</p>
                        <div class="feature-list">
                            <div><i class='bx bx-calendar-check'></i> Schedule service appointments instantly</div>
                            <div><i class='bx bx-car'></i> Manage your garage vehicles and history</div>
                            <div><i class='bx bx-line-chart'></i> Track service progress live from intake to delivery</div>
                            <div><i class='bx bx-file-find'></i> Review estimates, work orders, and invoices</div>
                            <div><i class='bx bx-headset'></i> Request support and communicate with your service team</div>
                        </div>
                        <div class="cta-buttons">
                            <a class="btn-primary" href="login.php?redirect=customer_dash.php">Open Customer Portal</a>
                            <a class="btn-ghost" href="register.php?redirect=customer_dash.php">Register an Account</a>
                        </div>
                    </div>
            </div>
            <div class="feature-panel">
                <div class="panel-title">
                    <div>
                        <h3>Quick Fleet Insights</h3>
                        <small>Live telemetry & statuses</small>
                    </div>
                </div>
                <div class="vehicle-card">
                    <div class="stat-line"><span>Vehicle</span><strong>Silencer 680</strong></div>
                    <div class="stat-line"><span>Status</span><span class="status-progress">In service</span></div>
                    <div class="stat-line"><span>Bay</span><strong>Zone 3</strong></div>
                    <div class="stat-line"><span>Mechanic</span><strong>R. Kumar</strong></div>
                </div>
                <div class="vehicle-card">
                    <div class="stat-line"><span>Vehicle</span><strong>RapidRider 1100</strong></div>
                    <div class="stat-line"><span>Status</span><span class="status-ready">Ready</span></div>
                    <div class="stat-line"><span>Bay</span><strong>Zone 1</strong></div>
                    <div class="stat-line"><span>Mechanic</span><strong>S. Patel</strong></div>
                </div>
                <div class="vehicle-card">
                    <div class="stat-line"><span>Vehicle</span><strong>Vortex 4x4</strong></div>
                    <div class="stat-line"><span>Status</span><span class="status-alert">Parts pending</span></div>
                    <div class="stat-line"><span>Bay</span><strong>Zone 6</strong></div>
                    <div class="stat-line"><span>Mechanic</span><strong>V. Singh</strong></div>
                </div>
            </div>
        </section>

        <section id="tracking" class="section grid-cards reveal">
            <div class="vehicle-card neon-hover">
                <h4>Garage Alert</h4>
                <small>Incoming luxury sedan</small>
                <p>Scheduled arrival in 12 minutes with full diagnostics request.</p>
                <div class="stat-line"><span>Job ref</span><strong>#PS-0426</strong></div>
            </div>
            <div class="vehicle-card neon-hover">
                <h4>Priority Ride</h4>
                <small>Superbike service</small>
                <p>Full brake bleed and race suspension tuning.</p>
                <div class="stat-line"><span>ETA</span><strong>22 min</strong></div>
            </div>
            <div class="vehicle-card neon-hover">
                <h4>Fleet Status</h4>
                <small>24 vehicles active</small>
                <p>Balanced across 8 technicians, auto-assigned by load.</p>
                <div class="stat-line"><span>Efficiency</span><strong>92%</strong></div>
            </div>
        </section>

        <section id="mechanics" class="section panel-grid reveal">
            <div class="team-panel">
                <div class="panel-title">
                    <div>
                        <h3>Mechanic Management</h3>
                        <small>Workload & expertise</small>
                    </div>
                </div>
                <div class="mechanic-list">
                    <div class="mechanic-card">
                        <div>
                            <strong>Rajesh Kumar</strong>
                            <div class="capacity">Lead mechanic · 6 active jobs</div>
                        </div>
                        <span class="chip status-progress">Load balanced</span>
                    </div>
                    <div class="mechanic-card">
                        <div>
                            <strong>Sahana Patel</strong>
                            <div class="capacity">Precision specialist · 4 active jobs</div>
                        </div>
                        <span class="chip status-ready">Available</span>
                    </div>
                    <div class="mechanic-card">
                        <div>
                            <strong>Vikram Singh</strong>
                            <div class="capacity">High-performance · 7 active jobs</div>
                        </div>
                        <span class="chip status-alert">Parts hold</span>
                    </div>
                </div>
            </div>
            <div class="review-panel">
                <div class="panel-title">
                    <div>
                        <h3>Customer Reviews</h3>
                        <small>Premium feedback from the pit lane</small>
                    </div>
                </div>
                <div class="review-grid">
                    <div class="review-card">
                        <div>
                            <strong>“Unmatched speed and polish.”</strong>
                            <p>Service felt like a race-day pit stop — flawless communication and luxurious detail.</p>
                        </div>
                        <div class="stars"><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star-half'></i></div>
                    </div>
                    <div class="review-card">
                        <div>
                            <strong>“Boardroom quality, track speed.”</strong>
                            <p>First-class treatment with real-time status updates and premium support.</p>
                        </div>
                        <div class="stars"><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i></div>
                    </div>
                    <div class="review-card">
                        <div>
                            <strong>“Luxury meets performance.”</strong>
                            <p>This dashboard is exactly what a premium garage should feel like.</p>
                        </div>
                        <div class="stars"><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star-half'></i></div>
                    </div>
                </div>
            </div>
        </section>

        <section id="pricing" class="section reveal">
            <div class="panel-title">
                <div>
                    <h3>Service Pricing Cards</h3>
                    <small>Elite packages for every performance need</small>
                </div>
            </div>
            <div class="pricing-grid">
                <div class="pricing-card neon-hover">
                    <h4>Race Day Prep</h4>
                    <div class="price">₹ 4,999</div>
                    <ul>
                        <li>Full systems check</li>
                        <li>Brake & suspension tune</li>
                        <li>Priority bay booking</li>
                        <li>Performance report</li>
                    </ul>
                    <a class="btn-primary cta" href="#booking">Select Package</a>
                </div>
                <div class="pricing-card neon-hover">
                    <h4>Premium Overhaul</h4>
                    <div class="price">₹ 9,999</div>
                    <ul>
                        <li>Engine calibration</li>
                        <li>Turbo diagnostics</li>
                        <li>Luxury interior refresh</li>
                        <li>Track-ready dynamics</li>
                        <li>Concierge delivery</li>
                    </ul>
                    <a class="btn-primary cta" href="#booking">Select Package</a>
                </div>
                <div class="pricing-card neon-hover">
                    <h4>Championship Track Elite</h4>
                    <div class="price">₹ 19,999</div>
                    <ul>
                        <li>Custom ECU remapping</li>
                        <li>Dyno tuning optimization</li>
                        <li>Carbon component check</li>
                        <li>Dedicated race engineer</li>
                        <li>Lifetime telemetry access</li>
                    </ul>
                    <a class="btn-primary cta" href="#booking">Select Package</a>
                </div>
            </div>
        </section>

        <footer class="footer">
            <p>&copy; 2026 8099 PitStop. All rights reserved. Precision Engineering & Luxury Command.</p>
        </footer>
    </div>
    <script>
        // Smooth scroll for internal anchors
        document.querySelectorAll('a[href^="#"]').forEach(function(el){
            el.addEventListener('click', function(e){
                var target = this.getAttribute('href');
                if (target === '#' || target === '') return;
                var dest = document.querySelector(target);
                if (dest) {
                    e.preventDefault();
                    window.scrollTo({ top: dest.offsetTop - 80, behavior: 'smooth' });
                }
            });
        });

        // CTA buttons that require login should redirect to login page when clicked
        document.querySelectorAll('a.btn-primary[href="login.php"]').forEach(function(el){
            el.addEventListener('click', function(e){
                // allow default navigation to login.php
            });
        });
    </script>
</body>
</html>