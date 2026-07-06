<?php
// admin_dashboard.php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();

$memberCount     = $db->query("SELECT COUNT(*) c FROM Members")->fetch()['c'] ?? 0;
$pendingCount    = $db->query("SELECT COUNT(*) c FROM Members WHERE status = 'pending'")->fetch()['c'] ?? 0;
$instructorCount = $db->query("SELECT COUNT(*) c FROM Instructor")->fetch()['c'] ?? 0;
$monthRevenue    = $db->query("SELECT COALESCE(SUM(amount),0) s FROM Payment WHERE status = 'completed' AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())")->fetch()['s'] ?? 0;

// Recent pending members for quick approval
$pending = $db->query("SELECT member_id, first_name, last_name, email, created_at FROM Members WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - USTED-K Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat { background: var(--bg-card); padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); }
        .stat .number { font-size: 2.25rem; font-weight: 800; background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat .label { color: var(--text-muted); font-size: 0.85rem; margin-top: 4px; }
        .stat i { float: right; font-size: 1.5rem; color: var(--primary-light); opacity: 0.4; }
        @media (max-width: 768px) { .stats { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 480px) { .stats { grid-template-columns: 1fr; } }
    </style>
</head>
<body style="background: var(--bg-dark);">
    <div class="main-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <img src="assets/images/logo.png" alt="Logo" onerror="this.style.display='none'">
                <span>USTED-K Gym</span>
            </div>
            <nav class="sidebar-menu">
                <div class="sidebar-menu-label">Main</div>
                <a href="admin_dashboard.php" class="sidebar-item active"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="admin_members.php" class="sidebar-item"><i class="fas fa-users"></i> Members</a>
                <a href="admin_instructors.php" class="sidebar-item"><i class="fas fa-chalkboard-teacher"></i> Instructors</a>
                <a href="admin_memberships.php" class="sidebar-item"><i class="fas fa-id-card"></i> Memberships</a>
                <div class="sidebar-menu-label" style="margin-top: 20px;">Training</div>
                <a href="admin_training_types.php" class="sidebar-item"><i class="fas fa-dumbbell"></i> Training Types</a>
                <a href="admin_training_sections.php" class="sidebar-item"><i class="fas fa-calendar-alt"></i> Sections</a>
                <div class="sidebar-menu-label" style="margin-top: 20px;">Financial</div>
                <a href="admin_payments.php" class="sidebar-item"><i class="fas fa-credit-card"></i> Payments</a>
                <a href="admin_reports.php" class="sidebar-item"><i class="fas fa-chart-line"></i> Reports</a>
                <div class="sidebar-menu-label" style="margin-top: 20px;">System</div>
                <a href="admin_logs.php" class="sidebar-item"><i class="fas fa-list"></i> Logs</a>
                <a href="admin_backup.php" class="sidebar-item"><i class="fas fa-database"></i> Backup</a>
                <a href="logout.php" class="sidebar-item" style="margin-top: 20px; color: #FF6B6B;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div>
                        <h2>Dashboard</h2>
                        <div class="breadcrumb">Admin / <span>Dashboard</span></div>
                    </div>
                </div>
                <div class="top-nav-right">
                    <div class="profile-dropdown">
                        <div class="info">
                            <div class="name"><?= htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin') ?></div>
                            <div class="role"><?= ucfirst(str_replace('_', ' ', $_SESSION['admin_role'] ?? 'admin')) ?></div>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="stats animate-fade-in">
                <div class="stat"><i class="fas fa-users"></i><div class="number"><?= $memberCount ?></div><div class="label">Total Members</div></div>
                <div class="stat"><i class="fas fa-user-clock"></i><div class="number"><?= $pendingCount ?></div><div class="label">Pending Approvals</div></div>
                <div class="stat"><i class="fas fa-chalkboard-teacher"></i><div class="number"><?= $instructorCount ?></div><div class="label">Instructors</div></div>
                <div class="stat"><i class="fas fa-money-bill-wave"></i><div class="number"><?= formatCurrency($monthRevenue) ?></div><div class="label">This Month's Revenue</div></div>
            </div>

            <div class="card animate-fade-in">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h5><i class="fas fa-user-clock" style="color: var(--primary-light);"></i> Pending Approvals</h5>
                    <a href="admin_members.php?status=pending" style="color: var(--primary-light); font-size: 0.85rem;">View all &rarr;</a>
                </div>
                <div class="card-body">
                    <?php if (empty($pending)): ?>
                        <p style="color: var(--text-muted);">No members waiting for approval. 🎉</p>
                    <?php else: ?>
                        <div class="table-wrapper">
                        <table class="table">
                            <thead><tr><th>Name</th><th>Email</th><th>Registered</th><th style="text-align:right;">Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($pending as $p): ?>
                                <tr>
                                    <td style="font-weight:600;"><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></td>
                                    <td><?= htmlspecialchars($p['email']) ?></td>
                                    <td style="font-size:0.8rem;"><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
                                    <td style="text-align:right;">
                                        <a href="approve_member.php?id=<?= $p['member_id'] ?>" class="btn btn-primary" style="padding:6px 14px; font-size:0.8rem;"><i class="fas fa-check"></i> Approve</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="footer">&copy; <?= date('Y') ?> USTED-K Gym Center • Admin Panel</div>
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
