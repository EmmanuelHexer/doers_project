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

// Member info
$stmt = $db->prepare("SELECT m.*, mt.name as membership_name, mt.fee, mt.duration_months,
        CONCAT(i.first_name, ' ', i.last_name) as instructor_name
        FROM Members m
        LEFT JOIN Membership_Type mt ON m.membership_type_id = mt.membership_type_id
        LEFT JOIN Instructor i ON m.assigned_instructor_id = i.instructor_id
        WHERE m.member_id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Stats
$stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) total FROM Payment WHERE member_id = ? AND status = 'completed'");
$stmt->execute([$member_id]);
$totalPaid = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) c FROM Member_Section WHERE member_id = ? AND status = 'active'");
$stmt->execute([$member_id]);
$activeSessions = $stmt->fetch()['c'];

$stmt = $db->prepare("SELECT COUNT(*) c FROM Payment WHERE member_id = ? AND status = 'pending'");
$stmt->execute([$member_id]);
$pendingPayments = $stmt->fetch()['c'];

// Days until membership expires
$daysLeft = null;
if (!empty($member['membership_end_date'])) {
    $end = strtotime($member['membership_end_date']);
    $daysLeft = (int) floor(($end - strtotime(date('Y-m-d'))) / 86400);
}

// Upcoming sessions
$stmt = $db->prepare("SELECT ts.*, tt.name as training_name,
        CONCAT(i.first_name, ' ', i.last_name) as instructor_name
        FROM Member_Section ms
        JOIN Training_Section ts ON ms.section_id = ts.section_id
        JOIN Training_Type tt ON ts.training_type_id = tt.training_type_id
        LEFT JOIN Instructor i ON ts.instructor_id = i.instructor_id
        WHERE ms.member_id = ? AND ms.status = 'active' AND ts.status = 'active'
        ORDER BY FIELD(ts.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), ts.start_time
        LIMIT 5");
$stmt->execute([$member_id]);
$upcomingSessions = $stmt->fetchAll();

// Recent payments
$stmt = $db->prepare("SELECT amount, status, payment_date, payment_method FROM Payment WHERE member_id = ? ORDER BY payment_date DESC LIMIT 4");
$stmt->execute([$member_id]);
$recentPayments = $stmt->fetchAll();

$active = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - USTED-K Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        .welcome-card { background: var(--primary-gradient); border-radius: var(--radius-lg); padding: 32px; color: #fff; margin-bottom: 24px; }
        .welcome-card h2 { font-size: 1.75rem; margin-bottom: 4px; }
        .welcome-card p { opacity: 0.9; margin-bottom: 12px; }
        .badge-status { display: inline-block; padding: 4px 16px; border-radius: 9999px; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; background: rgba(255,255,255,0.2); color: #fff; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--bg-card); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); transition: all var(--transition-base); }
        .stat-card:hover { transform: translateY(-4px); border-color: var(--primary); box-shadow: var(--shadow-md); }
        .stat-card i { float: right; font-size: 1.4rem; color: var(--primary-light); opacity: 0.4; }
        .stat-card .label { color: var(--text-muted); font-size: 0.75rem; }
        .stat-card .value { font-size: 1.35rem; font-weight: 700; margin-top: 2px; }
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 24px; }
        .quick-action { background: var(--bg-card); padding: 20px 16px; border-radius: var(--radius-md); text-align: center; text-decoration: none; color: var(--text-primary); border: 1px solid var(--border-color); transition: all var(--transition-base); }
        .quick-action:hover { transform: translateY(-4px); border-color: var(--primary); box-shadow: var(--shadow-md); }
        .quick-action i { font-size: 1.5rem; color: var(--primary-light); display: block; margin-bottom: 8px; }
        .quick-action span { font-size: 0.8rem; font-weight: 600; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }
        .session-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border-color); }
        .session-item:last-child { border-bottom: none; }
        .session-item .info h6 { font-size: 0.9rem; font-weight: 600; }
        .session-item .info p { font-size: 0.75rem; color: var(--text-muted); }
        .session-item .time { text-align: right; font-size: 0.75rem; color: var(--text-muted); }
        .session-item .time strong { color: var(--text-primary); }
    </style>
</head>
<body style="background: var(--bg-dark);">
    <div class="main-wrapper">
        <?php include __DIR__ . '/includes/member_sidebar.php'; ?>
        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div>
                        <h2>Dashboard</h2>
                        <div class="breadcrumb">Home / <span>Dashboard</span></div>
                    </div>
                </div>
                <div class="top-nav-right">
                    <div class="profile-dropdown">
                        <div class="avatar-initial"><?= strtoupper(substr($_SESSION['member_username'] ?? 'U', 0, 1)) ?></div>
                        <div class="info">
                            <div class="name"><?= htmlspecialchars($_SESSION['member_name']) ?></div>
                            <div class="role">Member</div>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="welcome-card animate-fade-in">
                <h2>Welcome back, <?= htmlspecialchars($member['first_name']) ?>! 👋</h2>
                <p>Keep pushing your limits. Stay consistent. Stay strong.</p>
                <span class="badge-status"><i class="fas fa-circle" style="font-size: 0.5rem;"></i> <?= ucfirst($member['status']) ?></span>
                <?php if ($daysLeft !== null): ?>
                    <span class="badge-status" style="margin-left:8px;"><i class="fas fa-clock"></i>
                        <?= $daysLeft >= 0 ? $daysLeft . ' days left' : 'Expired' ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="stats-grid animate-fade-in">
                <div class="stat-card"><i class="fas fa-id-card"></i><div class="label">Membership</div><div class="value"><?= htmlspecialchars($member['membership_name'] ?? 'None') ?></div></div>
                <div class="stat-card"><i class="fas fa-calendar-check"></i><div class="label">Active Sessions</div><div class="value"><?= $activeSessions ?></div></div>
                <div class="stat-card"><i class="fas fa-money-bill-wave"></i><div class="label">Total Paid</div><div class="value"><?= formatCurrency($totalPaid) ?></div></div>
                <div class="stat-card"><i class="fas fa-user-tie"></i><div class="label">Instructor</div><div class="value"><?= htmlspecialchars($member['instructor_name'] ?? 'Not Assigned') ?></div></div>
            </div>

            <div class="quick-actions animate-fade-in">
                <a href="book_session.php" class="quick-action"><i class="fas fa-calendar-plus"></i><span>Book Session</span></a>
                <a href="make_payment.php" class="quick-action"><i class="fas fa-hand-holding-usd"></i><span>Make Payment</span></a>
                <a href="profile.php" class="quick-action"><i class="fas fa-user-edit"></i><span>Update Profile</span></a>
                <a href="my_sessions.php" class="quick-action"><i class="fas fa-list"></i><span>My Sessions</span></a>
            </div>

            <div class="grid-2">
                <div class="card animate-fade-in">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-check" style="color: var(--primary-light);"></i> Upcoming Sessions</h5>
                        <a href="my_sessions.php" style="color: var(--primary-light); font-size: 0.75rem;">View All →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingSessions)): ?>
                            <p style="color: var(--text-muted); text-align: center; padding: 20px 0;">
                                <i class="fas fa-calendar-plus" style="font-size: 2rem; display: block; margin-bottom: 8px; color: var(--primary-light);"></i>
                                No upcoming sessions.<br>
                                <a href="book_session.php" style="color: var(--primary-light);">Book a session now</a>
                            </p>
                        <?php else: foreach ($upcomingSessions as $session): ?>
                            <div class="session-item">
                                <div class="info">
                                    <h6><?= htmlspecialchars($session['training_name']) ?></h6>
                                    <p><i class="fas fa-user"></i> <?= htmlspecialchars($session['instructor_name'] ?? 'TBA') ?></p>
                                </div>
                                <div class="time">
                                    <div><strong><?= $session['day_of_week'] ?></strong></div>
                                    <div><?= date('h:i A', strtotime($session['start_time'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <div class="card animate-fade-in">
                    <div class="card-header">
                        <h5><i class="fas fa-receipt" style="color: var(--primary-light);"></i> Recent Payments</h5>
                        <a href="payments.php" style="color: var(--primary-light); font-size: 0.75rem;">View All →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentPayments)): ?>
                            <p style="color: var(--text-muted); text-align: center; padding: 20px 0;">
                                <i class="fas fa-hand-holding-usd" style="font-size: 2rem; display: block; margin-bottom: 8px; color: var(--primary-light);"></i>
                                No payments yet.<br>
                                <a href="make_payment.php" style="color: var(--primary-light);">Make a payment</a>
                            </p>
                        <?php else: foreach ($recentPayments as $p): ?>
                            <div class="session-item">
                                <div class="info">
                                    <h6><?= formatCurrency($p['amount']) ?></h6>
                                    <p><?= htmlspecialchars(ucfirst(str_replace('_',' ',$p['payment_method']))) ?></p>
                                </div>
                                <div class="time">
                                    <div><span class="status-badge <?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></div>
                                    <div><?= date('M d, Y', strtotime($p['payment_date'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <div class="footer">&copy; <?= date('Y') ?> USTED-K Gym Center</div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
        });
    </script>
</body>
</html>
