<?php
// admin_login.php - ADMIN LOGIN WITH VISIBLE BACK TO HOME BUTTON
session_start();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin_dashboard.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';

$error = '';
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $sql = "SELECT admin_id, username, password_hash, role, status, full_name 
                FROM Admin_Users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();
        
        if ($admin && Security::verifyPassword($password, $admin['password_hash'])) {
            if ($admin['status'] === 'active') {
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_fullname'] = $admin['full_name'];
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['last_activity'] = time();
                
                header('Location: admin_dashboard.php');
                exit;
            } else {
                $error = 'Account is inactive or suspended.';
            }
        } else {
            $error = 'Invalid admin credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - USTED-K Gym Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
            background: #0D0D0D;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: 
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="90" font-size="90" opacity="0.04">🏋️</text></svg>') repeat,
                radial-gradient(ellipse at 30% 40%, rgba(255,107,0,0.1) 0%, transparent 60%),
                radial-gradient(ellipse at 70% 60%, rgba(255,140,0,0.07) 0%, transparent 60%),
                linear-gradient(180deg, #0D0D0D 0%, #1A0A00 100%);
            z-index: 0;
        }

        .bg-particles {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .bg-particles span {
            position: absolute;
            width: 5px;
            height: 5px;
            background: rgba(255,107,0,0.12);
            border-radius: 50%;
            animation: floatParticle 25s infinite linear;
        }

        .bg-particles span:nth-child(1) { left: 5%; animation-delay: 0s; animation-duration: 20s; }
        .bg-particles span:nth-child(2) { left: 15%; animation-delay: 3s; animation-duration: 23s; width: 7px; height: 7px; }
        .bg-particles span:nth-child(3) { left: 25%; animation-delay: 5s; animation-duration: 18s; }
        .bg-particles span:nth-child(4) { left: 35%; animation-delay: 1s; animation-duration: 22s; width: 9px; height: 9px; }
        .bg-particles span:nth-child(5) { left: 45%; animation-delay: 4s; animation-duration: 26s; }
        .bg-particles span:nth-child(6) { left: 55%; animation-delay: 6s; animation-duration: 19s; }
        .bg-particles span:nth-child(7) { left: 65%; animation-delay: 2s; animation-duration: 24s; width: 6px; height: 6px; }
        .bg-particles span:nth-child(8) { left: 75%; animation-delay: 5s; animation-duration: 17s; }
        .bg-particles span:nth-child(9) { left: 85%; animation-delay: 1s; animation-duration: 21s; }
        .bg-particles span:nth-child(10) { left: 95%; animation-delay: 4s; animation-duration: 27s; }
        .bg-particles span:nth-child(11) { left: 20%; animation-delay: 6s; animation-duration: 20s; width: 8px; height: 8px; }
        .bg-particles span:nth-child(12) { left: 70%; animation-delay: 2s; animation-duration: 22s; }

        @keyframes floatParticle {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) scale(1); opacity: 0; }
        }

        .gym-decoration {
            position: fixed;
            z-index: 0;
            font-size: 22rem;
            opacity: 0.025;
            left: -8%;
            bottom: -8%;
            transform: rotate(10deg);
            color: #FF6B00;
            pointer-events: none;
        }

        .admin-login-container {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.8s ease forwards;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .admin-login-card {
            background: rgba(26, 26, 26, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 48px 40px;
            border: 1px solid rgba(255, 107, 0, 0.1);
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.8), 0 0 60px rgba(255, 107, 0, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.03);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .admin-login-card::after {
            content: '';
            position: absolute;
            top: -1px;
            left: 15%;
            right: 15%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #FF6B00, #FF8C00, transparent);
            opacity: 0.6;
        }

        .admin-login-card:hover {
            border-color: rgba(255, 107, 0, 0.2);
            box-shadow: 0 30px 100px rgba(0, 0, 0, 0.9), 0 0 80px rgba(255, 107, 0, 0.08);
        }

        .admin-login-header {
            text-align: center;
            margin-bottom: 36px;
        }

        .admin-login-header .logo-wrapper {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #FF6B00, #FF8C00, #FFA500);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 2.2rem;
            color: white;
            box-shadow: 0 8px 32px rgba(255, 107, 0, 0.3);
            transition: all 0.4s ease;
        }

        .admin-login-header .logo-wrapper:hover {
            transform: rotate(-5deg) scale(1.05);
            box-shadow: 0 12px 40px rgba(255, 107, 0, 0.4);
        }

        .admin-login-header h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            color: #FFFFFF;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .admin-login-header h1 span {
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .admin-login-header p {
            color: #A68A7A;
            font-size: 0.875rem;
            font-weight: 400;
        }

        .admin-login-header .role-badge {
            display: inline-block;
            margin-top: 10px;
            padding: 4px 18px;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            background: rgba(255, 107, 0, 0.12);
            color: #FF8C00;
            border: 1px solid rgba(255, 107, 0, 0.12);
        }

        .admin-login-header .role-badge i { margin-right: 6px; }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            animation: slideIn 0.4s ease;
        }

        .alert i { margin-top: 2px; font-size: 1rem; }
        .alert-danger { background: rgba(255, 68, 68, 0.08); color: #FF6B6B; border: 1px solid rgba(255, 68, 68, 0.1); }
        @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.8rem; margin-bottom: 6px; color: #C4B5A5; letter-spacing: 0.02em; text-transform: uppercase; }

        .form-group .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background: rgba(36, 36, 36, 0.8);
            border-radius: 12px;
            border: 1px solid rgba(255, 107, 0, 0.06);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .form-group .input-wrapper:focus-within {
            border-color: #FF6B00;
            box-shadow: 0 0 0 4px rgba(255, 107, 0, 0.08);
            background: rgba(36, 36, 36, 1);
            transform: scale(1.01);
        }

        .form-group .input-wrapper .icon {
            padding: 0 14px 0 16px;
            color: #6B5A4A;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .form-group .input-wrapper:focus-within .icon { color: #FF6B00; }

        .form-group .input-wrapper input {
            width: 100%;
            padding: 14px 16px 14px 0;
            background: transparent;
            border: none;
            outline: none;
            color: #FFFFFF;
            font-size: 0.95rem;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
        }

        .form-group .input-wrapper input::placeholder {
            color: #5A4A3A;
            font-weight: 400;
            font-size: 0.9rem;
        }

        .form-group .input-wrapper .toggle-password {
            padding: 0 16px 0 8px;
            background: none;
            border: none;
            color: #6B5A4A;
            cursor: pointer;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .form-group .input-wrapper .toggle-password:hover { color: #FF8C00; }

        .btn-admin-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow: hidden;
            margin-top: 4px;
        }

        .btn-admin-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #FF8C00, #FFA500);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .btn-admin-login:hover::before { opacity: 1; }
        .btn-admin-login:hover { transform: translateY(-2px); box-shadow: 0 8px 40px rgba(255, 107, 0, 0.35); }
        .btn-admin-login:active { transform: scale(0.98); }
        .btn-admin-login span, .btn-admin-login i { position: relative; z-index: 1; }

        /* ===== BACK TO HOME BUTTON - HIGHLY VISIBLE ===== */
        .back-home-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 12px 20px;
            margin-top: 12px;
            background: rgba(255, 255, 255, 0.05);
            color: #A68A7A;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 10px;
            border: 1px solid rgba(255, 107, 0, 0.15);
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .back-home-btn:hover {
            background: rgba(255, 107, 0, 0.1);
            color: #FF8C00;
            border-color: #FF6B00;
            transform: translateX(-4px);
            box-shadow: 0 4px 20px rgba(255, 107, 0, 0.1);
        }

        .back-home-btn i {
            font-size: 0.9rem;
            transition: transform 0.3s ease;
        }

        .back-home-btn:hover i {
            transform: translateX(-4px);
        }

        .admin-login-footer {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 107, 0, 0.06);
            text-align: center;
        }

        .admin-login-footer .footer-links {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .admin-login-footer .footer-links a {
            color: #6B5A4A;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.8rem;
            transition: color 0.2s ease;
        }

        .admin-login-footer .footer-links a:hover { color: #FF8C00; }
        .admin-login-footer .footer-links .divider { color: #3A2A1A; }

        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 16px;
            font-size: 0.7rem;
            color: #4A3A2A;
        }

        .security-badge i { color: #00B894; font-size: 0.5rem; }

        @media (max-width: 480px) {
            .admin-login-card { padding: 32px 24px; border-radius: 16px; }
            .admin-login-header h1 { font-size: 1.5rem; }
            .admin-login-header .logo-wrapper { width: 64px; height: 64px; font-size: 1.75rem; }
            .gym-decoration { font-size: 12rem; }
        }
    </style>
</head>
<body>

    <div class="bg-particles">
        <span></span><span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span><span></span>
        <span></span><span></span>
    </div>

    <div class="gym-decoration">🏋️</div>

    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-login-header">
                <div class="logo-wrapper">
                    <i class="fas fa-crown"></i>
                </div>
                <h1>Admin <span>Panel</span></h1>
                <p>Secure access to management dashboard</p>
                <span class="role-badge"><i class="fas fa-shield-alt"></i> Administrator</span>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-wrapper">
                        <span class="icon"><i class="fas fa-user-cog"></i></span>
                        <input type="text" id="username" name="username" placeholder="Enter admin username or email" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="icon"><i class="fas fa-lock"></i></span>
                        <input type="password" id="password" name="password" placeholder="Enter admin password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-admin-login">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Sign In as Admin</span>
                </button>
            </form>

            <div class="admin-login-footer">
                <div class="footer-links">
                    <a href="login.php"><i class="fas fa-user"></i> Member Login</a>
                </div>

                <!-- ===== BACK TO HOME BUTTON - VISIBLE ===== -->
                <a href="index.php" class="back-home-btn">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>

                <div class="security-badge">
                    <i class="fas fa-circle"></i> Encrypted Connection
                    <i class="fas fa-circle"></i> 256-bit SSL
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.className = 'fas fa-eye-slash';
            } else {
                password.type = 'password';
                eyeIcon.className = 'fas fa-eye';
            }
        }
    </script>

</body>
</html>