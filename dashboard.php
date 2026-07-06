<?php
// dashboard.php
session_start();

if (!isset($_SESSION['member_logged_in']) || $_SESSION['member_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();
$member_id = $_SESSION['member_id'];

// Get member info
$sql = "SELECT m.*, mt.name as membership_name, mt.fee, mt.duration_months,
        CONCAT(i.first_name, ' ', i.last_name) as instructor_name
        FROM Members m
        LEFT JOIN Membership_Type mt ON m.membership_type_id = mt.membership_type_id
        LEFT JOIN Instructor i ON m.assigned_instructor_id = i.instructor_id
        WHERE m.member_id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get stats
$sql = "SELECT SUM(amount) as total FROM Payment WHERE member_id = ? AND status = 'completed'";
$stmt = $db->prepare($sql);
$stmt->execute([$member_id]);
$totalPaid = $stmt->fetch()['total'] ?? 0;

$sql = "SELECT COUNT(*) as count FROM Member_Section WHERE member_id = ? AND status = 'active'";
$stmt = $db->prepare($sql);
$stmt->execute([$member_id]);
$activeSessions = $stmt->fetch()['count'] ?? 0;

// Upcoming sessions
$sql = "SELECT ts.*, tt.name as training_name, 
        CONCAT(i.first_name, ' ', i.last_name) as instructor_name
        FROM Member_Section ms
        JOIN Training_Section ts ON ms.section_id = ts.section_id
        JOIN Training_Type tt ON ts.training_type_id = tt.training_type_id
        JOIN Instructor i ON ts.instructor_id = i.instructor_id
        WHERE ms.member_id = ? AND ms.status = 'active' AND ts.status = 'active'
        ORDER BY FIELD(ts.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), ts.start_time
        LIMIT 5";
$stmt = $db->prepare($sql);
$stmt->execute([$member_id]);
$upcomingSessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - USTED-K Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0D0D0D; color: white; }
        :root { --primary: #FF6B00; --bg-card: #1A1A1A; --text-muted: #A68A7A; }
        .sidebar { width: 240px; background: #1A1A1A; border-right: 1px solid rgba(255,107,0,0.1); padding: 20px 0; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar-brand { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,107,0,0.1); font-weight: 800; font-size: 1.1rem; background: linear-gradient(135deg, #FF6B00, #FF8C00); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .sidebar-item { display: flex; align-items: center; gap: 12px; padding: 10px 16px; margin: 2px 12px; border-radius: 12px; color: #A68A7A; text-decoration: none; transition: all 0.3s; }
        .sidebar-item:hover { background: rgba(255,107,0,0.08); color: white; }
        .sidebar-item.active { background: linear-gradient(135deg, #FF6B00, #FF8C00); color: white; }
        .sidebar-item i { width: 20px; }
        .main-content { margin-left: 240px; padding: 24px 32px; }
        .welcome-card { background: linear-gradient(135deg, #FF6B00, #FF8C00); border-radius: 16px; padding: 32px; color: white; margin-bottom: 24px; position: relative; overflow: hidden; }
        .welcome-card h2 { font-size: 1.75rem; margin-bottom: 4px; }
        .welcome-card p { opacity: 0.9; }
        .badge-status { display: inline-block; padding: 4px 16px; border-radius: 9999px; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); color: white; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #1A1A1A; padding: 16px 20px; border-radius: 12px; border: 1px solid rgba(255,107,0,0.1); }
        .stat-card .label { color: #A68A7A; font-size: 0.75rem; }
        .stat-card .value { font-size: 1.25rem; font-weight: 700; }
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; margin-bottom: 24px; }
        .quick-action { background: #1A1A1A; padding: 16px; border-radius: 12px; text-align: center; text-decoration: none; color: white; border: 1px solid rgba(255,107,0,0.1); transition: all 0.3s; }
        .quick-action:hover { transform: translateY(-4px); border-color: #FF6B00; box-shadow: 0 4px 20px rgba(255,107,0,0.1); }
        .quick-action i { font-size: 1.5rem; color: #FF6B00; display: block; margin-bottom: 8px; }
        .quick-action span { font-size: 0.75rem; font-weight: 600; }
        .card { background: #1A1A1A; border-radius: 12px; border: 1px solid rgba(255,107,0,0.1); overflow: hidden; margin-bottom: 24px; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid rgba(255,107,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .card-header h5 { font-size: 1rem; }
        .card-header h5 i { color: #FF6B00; margin-right: 8px; }
        .card-body { padding: 16px 20px; }
        .session-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255,107,0,0.05); }
        .session-item:last-child { border-bottom: none; }
        .session-item .info h6 { font-size: 0.875rem; font-weight: 600; }
        .session-item .info p { font-size: 0.75rem; color: #A68A7A; }
        .session-item .time { font-size: 0.75rem; color: #A68A7A; text-align: right; }
        .session-item .time strong { color: white; }
        .footer-text { margin-top: 40px; padding-top: 20px; border-top: 1px solid rgba(255,107,0,0.1); text-align: center; font-size: 0.75rem; color: #A68A7A; }
        .btn { padding: 8px 16px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #FF6B00, #FF8C00); color: white; }
        .btn-primary:hover { transform: scale(1.05); }
        .btn-outline { background: transparent; color: white; border: 2px solid rgba(255,107,0,0.3); }
        .btn-outline:hover { background: rgba(255,107,0,0.1); }
        .btn-sm { padding: 4px 12px; font-size: 0.75rem; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); transition: transform 0.3s; } .sidebar.open { transform: translateX(0); } .main-content { margin-left: 0; padding: 16px; } .sidebar-toggle { display: block !important; } }
        .sidebar-toggle { display: none; position: fixed; top: 16px; left: 16px; z-index: 1000; background: #1A1A1A; border: 1px solid rgba(255,107,0,0.1); color: white; padding: 10px 12px; border-radius: 8px; cursor: pointer; }
        .top-nav { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 1px solid rgba(255,107,0,0.1); margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .top-nav h2 { font-size: 1.5rem; }
        .top-nav .breadcrumb { color: #A68A7A; font-size: 0.875rem; }
        .profile-dropdown { display: flex; align-items: center; gap: 10px; padding: 4px 12px 4px 4px; border-radius: 9999px; background: #1A1A1A; border: 1px solid rgba(255,107,0,0.1); cursor: pointer; }
        .profile-dropdown img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; background: #242424; }
        .profile-dropdown .name { font-weight: 600; font-size: 0.85rem; }
        .profile-dropdown .role { color: #A68A7A; font-size: 0.7rem; }
    </style>
</head>
<body>

    <!-- Sidebar Toggle (Mobile) -->
    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

    <div style="display: flex; min-height: 100vh;">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand"><i class="fas fa-dumbbell" style="-webkit-text-fill-color: #FF6B00;"></i> USTED-K Gym</div>
            <nav style="padding: 16px 0;">
                <a href="dashboard.php" class="sidebar-item active"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="profile.php" class="sidebar-item"><i class="fas fa-user"></i> My Profile</a>
                <a href="training.php" class="sidebar-item"><i class="fas fa-calendar-alt"></i> My Training</a>
                <a href="payments.php" class="sidebar-item"><i class="fas fa-credit-card"></i> Payments</a>
                <a href="book_session.php" class="sidebar-item"><i class="fas fa-calendar-plus"></i> Book Session</a>
                <a href="my_sessions.php" class="sidebar-item"><i class="fas fa-list"></i> My Sessions</a>
                <a href="logout.php" class="sidebar-item" style="color: #FF6B6B; margin-top: 20px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Top Nav -->
            <nav class="top-nav">
                <div>
                    <h2>Dashboard</h2>
                    <div class="breadcrumb">Home / <span>Dashboard</span></div>
                </div>
                <div class="profile-dropdown">
                    <img src="assets/images/default-avatar.png" alt="Profile" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['member_name']) ?>&background=FF6B00&color=fff'">
                    <div><div class="name"><?= htmlspecialchars($_SESSION['member_name']) ?></div><div class="role">Member</div></div>
                </div>
            </nav>

            <!-- Welcome -->
            <div class="welcome-card">
                <h2>Welcome back, <?= htmlspecialchars($member['first_name']) ?>! 👋</h2>
                <p>Keep pushing your limits. Stay consistent. Stay strong.</p>
                <span class="badge-status <?= $member['status'] ?>"><i class="fas fa-circle" style="font-size: 0.5rem;"></i> <?= ucfirst($member['status']) ?></span>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card"><div class="label">Membership</div><div class="value"><?= htmlspecialchars($member['membership_name'] ?? 'N/A') ?></div></div>
                <div class="stat-card"><div class="label">Active Sessions</div><div class="value"><?= $activeSessions ?></div></div>
                <div class="stat-card"><div class="label">Total Paid</div><div class="value"><?= formatCurrency($totalPaid) ?></div></div>
                <div class="stat-card"><div class="label">Instructor</div><div class="value"><?= htmlspecialchars($member['instructor_name'] ?? 'Not Assigned') ?></div></div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="book_session.php" class="quick-action"><i class="fas fa-calendar-plus"></i><span>Book Session</span></a>
                <a href="payments.php" class="quick-action"><i class="fas fa-hand-holding-usd"></i><span>Make Payment</span></a>
                <a href="profile.php" class="quick-action"><i class="fas fa-user-edit"></i><span>Update Profile</span></a>
                <a href="my_sessions.php" class="quick-action"><i class="fas fa-list"></i><span>My Sessions</span></a>
            </div>

            <!-- Upcoming Sessions -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-calendar-check"></i> Upcoming Sessions</h5>
                    <a href="my_sessions.php" style="color: #FF6B00; font-size: 0.75rem;">View All →</a>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingSessions)): ?>
                        <p style="color: #A68A7A; text-align: center; padding: 20px 0;">
                            <i class="fas fa-calendar-plus" style="font-size: 2rem; display: block; margin-bottom: 8px; color: #FF6B00;"></i>
                            No upcoming sessions booked.<br>
                            <a href="book_session.php" style="color: #FF6B00;">Book a session now</a>
                        </p>
                    <?php else: ?>
                        <?php foreach ($upcomingSessions as $session): ?>
                            <div class="session-item">
                                <div class="info">
                                    <h6><?= htmlspecialchars($session['training_name']) ?></h6>
                                    <p><i class="fas fa-user"></i> <?= htmlspecialchars($session['instructor_name']) ?> <span style="margin: 0 8px;">•</span> <i class="fas fa-users"></i> <?= $session['capacity'] ?> capacity</p>
                                </div>
                                <div class="time">
                                    <div><strong><?= $session['day_of_week'] ?></strong></div>
                                    <div><?= date('h:i A', strtotime($session['start_time'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="footer-text">&copy; <?= date('Y') ?> USTED-K Gym Center</div>
        </main>
    </div>

    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
        });
        // Close sidebar on outside click (mobile)
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    </script>
</body>
</html>