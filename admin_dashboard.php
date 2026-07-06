<?php
// admin_dashboard.php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

$memberCount = $db->query("SELECT COUNT(*) as count FROM Members")->fetch()['count'] ?? 0;
$pendingCount = $db->query("SELECT COUNT(*) as count FROM Members WHERE status = 'pending'")->fetch()['count'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - USTED-K Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0D0D0D; color: white; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid rgba(255,107,0,0.1); padding-bottom: 20px; }
        .header h1 { font-size: 2rem; }
        .header a { color: #FF6B6B; text-decoration: none; font-weight: 600; }
        .header a:hover { text-decoration: underline; }
        .welcome { background: linear-gradient(135deg, #FF6B00, #FF8C00); padding: 30px; border-radius: 16px; margin-bottom: 30px; }
        .welcome h2 { color: white; font-size: 1.5rem; }
        .welcome p { color: rgba(255,255,255,0.8); }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat { background: #1A1A1A; padding: 24px; border-radius: 12px; border: 1px solid rgba(255,107,0,0.1); text-align: center; }
        .stat .number { font-size: 2.5rem; font-weight: 800; background: linear-gradient(135deg, #FF6B00, #FF8C00); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat .label { color: #A68A7A; font-size: 0.875rem; margin-top: 4px; }
        .card { background: #1A1A1A; padding: 24px; border-radius: 12px; border: 1px solid rgba(255,107,0,0.1); }
        .card h2 { margin-bottom: 8px; }
        .card p { color: #A68A7A; }
        .card .success { color: #00B894; }
        .session-info { font-size: 0.75rem; color: #4A3A2A; margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,107,0,0.06); }
        @media (max-width: 768px) { .stats { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 480px) { .stats { grid-template-columns: 1fr; } body { padding: 20px; } }
    </style>
</head>
<body>
    <div style="max-width: 1200px; margin: 0 auto;">
        <div class="header">
            <h1>🏋️ Admin Dashboard</h1>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        
        <div class="welcome">
            <h2>👋 Welcome, <?= htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin') ?>!</h2>
            <p>You have successfully logged in to the admin panel.</p>
        </div>
        
        <div class="stats">
            <div class="stat">
                <div class="number"><?= $memberCount ?></div>
                <div class="label">Total Members</div>
            </div>
            <div class="stat">
                <div class="number"><?= $pendingCount ?></div>
                <div class="label">Pending Approvals</div>
            </div>
            <div class="stat">
                <div class="number">0</div>
                <div class="label">Active Instructors</div>
            </div>
            <div class="stat">
                <div class="number">₱0</div>
                <div class="label">Monthly Revenue</div>
            </div>
        </div>
        
        <div class="card">
            <h2><span class="success">✅</span> Login Successful!</h2>
            <p>You are now logged in to the admin panel. You can manage members, instructors, payments, and more.</p>
            <div class="session-info">
                <i class="fas fa-info-circle"></i> Session ID: <?= session_id() ?>
            </div>
        </div>
    </div>
</body>
</html>