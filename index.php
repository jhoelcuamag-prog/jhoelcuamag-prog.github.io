<?php
session_start();
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: staff/dashboard.php");
    }
    exit();
}
require_once 'config/db.php';

$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        if($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: staff/dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lombriks Inventory System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #2d1b69, #4a2c8a, #3a1f73);
            position: relative;
        }
        
        /* ===== BRIGHT BACKGROUND WITH GLOW ===== */
        .bg-logo-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2d1b69, #4a2c8a, #3a1f73);
            padding: 20px;
        }
        
        /* ===== BIG BRIGHT GLOW ===== */
        .bg-logo-container::before {
            content: '';
            position: absolute;
            width: 800px;
            height: 800px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.35), rgba(118, 75, 162, 0.15), transparent 70%);
            animation: glowPulse 4s ease-in-out infinite;
        }
        
        .bg-logo-container::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.10), transparent 60%);
            animation: glowPulse 6s ease-in-out infinite reverse;
        }
        
        @keyframes glowPulse {
            0% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(1); opacity: 0.6; }
        }
        
        .bg-logo-container .circle-wrapper {
            position: relative;
            width: 500px;
            height: 500px;
            animation: floatBg 20s ease-in-out infinite;
        }
        
        /* ===== BRIGHT GLOWING RINGS ===== */
        .bg-logo-container .circle-wrapper .glow-ring {
            position: absolute;
            top: -20px;
            left: -20px;
            width: 540px;
            height: 540px;
            border-radius: 50%;
            border: 2px solid rgba(102, 126, 234, 0.30);
            animation: spinRing 30s linear infinite;
            box-shadow: 0 0 80px rgba(102, 126, 234, 0.15);
        }
        
        .bg-logo-container .circle-wrapper .glow-ring::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            width: 540px;
            height: 540px;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top-color: rgba(102, 126, 234, 0.50);
            animation: spinRing 15s linear infinite;
            filter: drop-shadow(0 0 30px rgba(102, 126, 234, 0.3));
        }
        
        .bg-logo-container .circle-wrapper .glow-ring::after {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            width: 520px;
            height: 520px;
            border-radius: 50%;
            border: 2px solid transparent;
            border-bottom-color: rgba(118, 75, 162, 0.45);
            animation: spinRingReverse 20s linear infinite;
            filter: drop-shadow(0 0 30px rgba(118, 75, 162, 0.3));
        }
        
        /* ===== BRIGHT SMOKE PARTICLES ===== */
        .bg-logo-container .circle-wrapper .smoke-particle-bg {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.10);
            filter: blur(30px);
            animation: smokeFloatBg 10s ease-in-out infinite;
        }
        
        .bg-logo-container .circle-wrapper .smoke-particle-bg:nth-child(2) {
            width: 150px;
            height: 150px;
            top: -40px;
            right: -40px;
            animation-delay: 0s;
        }
        
        .bg-logo-container .circle-wrapper .smoke-particle-bg:nth-child(3) {
            width: 180px;
            height: 180px;
            bottom: -50px;
            left: -50px;
            animation-delay: 3s;
        }
        
        .bg-logo-container .circle-wrapper .smoke-particle-bg:nth-child(4) {
            width: 120px;
            height: 120px;
            top: 50%;
            right: -60px;
            animation-delay: 6s;
        }
        
        .bg-logo-container .circle-wrapper .smoke-particle-bg:nth-child(5) {
            width: 130px;
            height: 130px;
            bottom: 40%;
            left: -50px;
            animation-delay: 8s;
        }
        
        /* ===== BRIGHT CIRCLE LOGO ===== */
        .bg-logo-container .circle-wrapper .circle-logo {
            width: 500px;
            height: 500px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.06);
            border: 3px solid rgba(255, 255, 255, 0.12);
            box-shadow: 
                0 0 100px rgba(102, 126, 234, 0.20),
                0 0 200px rgba(102, 126, 234, 0.08),
                inset 0 0 100px rgba(102, 126, 234, 0.10);
            position: relative;
        }
        
        .bg-logo-container .circle-wrapper .circle-logo::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(255,255,255,0.06) 0%, transparent 70%);
            animation: shimmerBg 8s ease-in-out infinite;
        }
        
        .bg-logo-container .circle-wrapper .circle-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.60;
            position: relative;
            z-index: 1;
            filter: brightness(1.3) contrast(1.1) drop-shadow(0 0 30px rgba(102,126,234,0.1));
        }
        
        /* ===== VERY LIGHT OVERLAY ===== */
        .bg-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(45, 27, 105, 0.10), rgba(74, 44, 138, 0.08), rgba(58, 31, 115, 0.10));
            z-index: 1;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 2;
        }
        
        /* ===== TRANSPARENT LOGIN CARD ===== */
        .login-card {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 30px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.25);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            border: 1px solid rgba(255,255,255,0.04);
        }
        
        .login-left {
            background: rgba(255, 255, 255, 0.01);
            padding: 50px;
            color: white;
            text-align: center;
            border-right: 1px solid rgba(255,255,255,0.02);
        }
        
        /* ===== LOGO IN LEFT PANEL ===== */
        .login-left .logo-wrapper {
            position: relative;
            width: 140px;
            height: 140px;
            margin: 0 auto 20px;
        }
        
        .login-left .logo-wrapper .smoke-particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            filter: blur(25px);
            animation: smokeFloat 8s ease-in-out infinite;
        }
        
        .login-left .logo-wrapper .smoke-particle:nth-child(1) {
            width: 120px;
            height: 120px;
            top: 10px;
            left: 10px;
            animation-delay: 0s;
        }
        .login-left .logo-wrapper .smoke-particle:nth-child(2) {
            width: 150px;
            height: 150px;
            top: -20px;
            right: -20px;
            animation-delay: 2s;
        }
        .login-left .logo-wrapper .smoke-particle:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: -10px;
            left: -10px;
            animation-delay: 4s;
        }
        .login-left .logo-wrapper .smoke-particle:nth-child(4) {
            width: 130px;
            height: 130px;
            bottom: -15px;
            right: -15px;
            animation-delay: 6s;
        }
        .login-left .logo-wrapper .smoke-particle:nth-child(5) {
            width: 90px;
            height: 90px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 3s;
        }
        
        .login-left .logo-wrapper .smoke-ring {
            position: absolute;
            top: -15px;
            left: -15px;
            width: 170px;
            height: 170px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.04);
            animation: smokePulse 3s ease-in-out infinite;
        }
        
        .login-left .logo-wrapper .smoke-ring::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            width: 170px;
            height: 170px;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top-color: rgba(255,255,255,0.10);
            animation: smokeSpin 8s linear infinite;
        }
        
        .login-left .logo-wrapper .smoke-ring::after {
            content: '';
            position: absolute;
            top: 5px;
            left: 5px;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            border: 2px solid transparent;
            border-bottom-color: rgba(255,255,255,0.06);
            animation: smokeSpinReverse 12s linear infinite;
        }
        
        .login-left .logo-container {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
            border: 3px solid rgba(255,255,255,0.08);
            transition: all 0.4s ease;
            position: relative;
            z-index: 2;
        }
        
        .login-left .logo-container:hover {
            transform: scale(1.08) rotate(3deg);
            border-color: rgba(255,255,255,0.20);
            box-shadow: 0 0 60px rgba(255,255,255,0.04);
        }
        
        .login-left .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            position: relative;
            z-index: 1;
        }
        
        .login-left h2 {
            font-weight: 700;
            letter-spacing: 2px;
            margin-top: 10px;
            color: #fff;
            text-shadow: 0 2px 20px rgba(0,0,0,0.2);
        }
        
        .login-left p {
            opacity: 0.7;
            font-weight: 300;
            font-size: 14px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .login-left .features {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .login-left .features .feature-item {
            background: rgba(255,255,255,0.04);
            border-radius: 12px;
            padding: 15px 20px;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.03);
            backdrop-filter: blur(5px);
        }
        
        .login-left .features .feature-item:hover {
            background: rgba(255,255,255,0.10);
            transform: translateY(-5px);
        }
        
        .login-left .features .feature-item i {
            font-size: 28px;
            display: block;
            margin-bottom: 8px;
        }
        
        .login-left .features .feature-item span {
            font-size: 12px;
            font-weight: 300;
            display: block;
            opacity: 0.7;
        }
        
        /* ===== TRANSPARENT LOGIN FORM ===== */
        .login-right {
            padding: 50px;
            background: rgba(255, 255, 255, 0.01);
        }
        
        .login-right .welcome-text {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-right .welcome-text h3 {
            font-weight: 700;
            color: #fff;
            text-shadow: 0 2px 20px rgba(0,0,0,0.2);
        }
        
        .login-right .welcome-text p {
            color: rgba(255,255,255,0.5);
            font-size: 14px;
        }
        
        .form-label {
            color: rgba(255,255,255,0.7);
            font-weight: 500;
        }
        
        .form-control {
            border-radius: 25px;
            padding: 12px 20px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.03);
            color: #fff;
            transition: all 0.3s;
        }
        
        .form-control::placeholder {
            color: rgba(255,255,255,0.15);
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.10);
            background: rgba(255,255,255,0.05);
            color: #fff;
        }
        
        .input-group-text {
            border-radius: 25px 0 0 25px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-right: none;
            color: rgba(255,255,255,0.3);
        }
        
        .input-group .form-control {
            border-radius: 0 25px 25px 0;
            border-left: none;
        }
        
        .btn-login {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.8), rgba(118, 75, 162, 0.8));
            color: white;
            border: none;
            padding: 12px;
            border-radius: 25px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 16px;
            backdrop-filter: blur(5px);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 30px rgba(102,126,234,0.3);
            color: white;
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9));
        }
        
        .demo-credentials {
            background: rgba(255,255,255,0.02);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid rgba(255,255,255,0.03);
        }
        
        .demo-credentials small {
            font-size: 12px;
            color: rgba(255,255,255,0.35);
        }
        
        .demo-credentials .badge-demo {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: rgba(102, 126, 234, 0.7);
            color: white;
        }
        
        .badge-staff {
            background: rgba(46, 204, 113, 0.7);
            color: white;
        }
        
        .demo-credentials strong {
            color: rgba(255,255,255,0.7);
        }
        
        hr {
            border-color: rgba(255,255,255,0.03);
        }
        
        .alert-danger {
            background: rgba(231, 76, 60, 0.10);
            border: 1px solid rgba(231, 76, 60, 0.12);
            color: #e74c3c;
            backdrop-filter: blur(5px);
        }
        
        .alert-danger .btn-close {
            filter: brightness(0) invert(1);
        }
        
        /* ===== ANIMATIONS ===== */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes floatBg {
            0% { transform: scale(1) rotate(0deg) translateY(0); }
            50% { transform: scale(1.02) rotate(1deg) translateY(-10px); }
            100% { transform: scale(1) rotate(0deg) translateY(0); }
        }
        
        @keyframes spinRing {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes spinRingReverse {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(-360deg); }
        }
        
        @keyframes smokeFloatBg {
            0% { transform: translate(0, 0) scale(1); opacity: 0.3; }
            50% { transform: translate(20px, -15px) scale(1.2); opacity: 0.6; }
            100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
        }
        
        @keyframes shimmerBg {
            0% { transform: translate(-30%, -30%) rotate(0deg); }
            50% { transform: translate(0%, 0%) rotate(180deg); }
            100% { transform: translate(30%, 30%) rotate(360deg); }
        }
        
        @keyframes smokeFloat {
            0% { transform: translate(0, 0) scale(1) rotate(0deg); opacity: 0.2; }
            25% { transform: translate(15px, -10px) scale(1.1) rotate(45deg); opacity: 0.5; }
            50% { transform: translate(-10px, 15px) scale(0.9) rotate(90deg); opacity: 0.2; }
            75% { transform: translate(10px, 5px) scale(1.2) rotate(135deg); opacity: 0.6; }
            100% { transform: translate(0, 0) scale(1) rotate(180deg); opacity: 0.2; }
        }
        
        @keyframes smokePulse {
            0% { transform: scale(1); opacity: 0.2; }
            50% { transform: scale(1.05); opacity: 0.5; }
            100% { transform: scale(1); opacity: 0.2; }
        }
        
        @keyframes smokeSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes smokeSpinReverse {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(-360deg); }
        }
        
        .login-card {
            animation: fadeIn 0.6s ease-out;
        }
        
        @media (max-width: 992px) {
            .bg-logo-container .circle-wrapper {
                width: 350px;
                height: 350px;
            }
            .bg-logo-container .circle-wrapper .circle-logo {
                width: 350px;
                height: 350px;
            }
            .bg-logo-container .circle-wrapper .glow-ring {
                width: 390px;
                height: 390px;
                top: -20px;
                left: -20px;
            }
            .bg-logo-container .circle-wrapper .glow-ring::before {
                width: 390px;
                height: 390px;
            }
            .bg-logo-container .circle-wrapper .glow-ring::after {
                width: 370px;
                height: 370px;
            }
        }
        
        @media (max-width: 768px) {
            .login-left {
                padding: 30px;
                border-right: none;
                border-bottom: 1px solid rgba(255,255,255,0.02);
            }
            .login-right {
                padding: 30px;
            }
            .login-left .features {
                flex-direction: column;
                gap: 10px;
            }
            .login-left .logo-wrapper {
                width: 100px;
                height: 100px;
            }
            .login-left .logo-container {
                width: 100px;
                height: 100px;
                padding: 18px;
            }
            .bg-logo-container .circle-wrapper {
                width: 250px;
                height: 250px;
            }
            .bg-logo-container .circle-wrapper .circle-logo {
                width: 250px;
                height: 250px;
            }
            .bg-logo-container .circle-wrapper .glow-ring {
                width: 290px;
                height: 290px;
                top: -20px;
                left: -20px;
            }
            .bg-logo-container .circle-wrapper .glow-ring::before {
                width: 290px;
                height: 290px;
            }
            .bg-logo-container .circle-wrapper .glow-ring::after {
                width: 270px;
                height: 270px;
            }
        }
        
        @media (max-width: 480px) {
            .login-left .logo-wrapper {
                width: 80px;
                height: 80px;
            }
            .login-left .logo-container {
                width: 80px;
                height: 80px;
                padding: 12px;
            }
            .bg-logo-container .circle-wrapper {
                width: 180px;
                height: 180px;
            }
            .bg-logo-container .circle-wrapper .circle-logo {
                width: 180px;
                height: 180px;
            }
            .bg-logo-container .circle-wrapper .glow-ring {
                width: 220px;
                height: 220px;
                top: -20px;
                left: -20px;
            }
            .bg-logo-container .circle-wrapper .glow-ring::before {
                width: 220px;
                height: 220px;
            }
            .bg-logo-container .circle-wrapper .glow-ring::after {
                width: 200px;
                height: 200px;
            }
            .bg-logo-container .circle-wrapper .smoke-particle-bg:nth-child(2),
            .bg-logo-container .circle-wrapper .smoke-particle-bg:nth-child(3) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- ===== VERY BRIGHT BACKGROUND LOGO - CIRCLE ===== -->
    <div class="bg-logo-container">
        <div class="circle-wrapper">
            <div class="glow-ring"></div>
            <div class="smoke-particle-bg"></div>
            <div class="smoke-particle-bg"></div>
            <div class="smoke-particle-bg"></div>
            <div class="smoke-particle-bg"></div>
            <div class="circle-logo">
                <img src="uploads/493750310_122110653854831961_6699102984930319989_n.jpg" alt="Lombriks Logo" onerror="this.style.display='none'">
            </div>
        </div>
    </div>
    <div class="bg-overlay"></div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="row g-0">
                <div class="col-md-6 login-left">
                    <div class="logo-wrapper">
                        <div class="smoke-particle"></div>
                        <div class="smoke-particle"></div>
                        <div class="smoke-particle"></div>
                        <div class="smoke-particle"></div>
                        <div class="smoke-particle"></div>
                        <div class="smoke-ring"></div>
                        <div class="logo-container">
                            <img src="uploads/logo.jpg" alt="Lombriks Logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2997/2997929.png'">
                        </div>
                    </div>
                    
                    <h2>Lombriks</h2>
                    <p>Inventory Management System</p>
                    
                    <div class="features">
                        <div class="feature-item">
                            <i class="fas fa-boxes"></i>
                            <span>Inventory</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-chart-line"></i>
                            <span>Analytics</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-users"></i>
                            <span>Management</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <small style="opacity: 0.5;">
                            <i class="fas fa-shield-alt"></i> Secure System
                        </small>
                    </div>
                </div>
                <div class="col-md-6 login-right">
                    <div class="welcome-text">
                        <h3>Welcome Back!</h3>
                        <p>Please login to your account</p>
                    </div>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-500">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="username" class="form-control" required placeholder="Enter username">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-500">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" class="form-control" required placeholder="Enter password" id="password">
                            </div>
                            <div class="text-end mt-1">
                                <small style="color: rgba(255,255,255,0.3); cursor: pointer;">
                                    <i class="far fa-eye" id="togglePassword"></i>
                                </small>
                            </div>
                        </div>
                        <button type="submit" class="btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            if (password.type === 'password') {
                password.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    </script>
   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>