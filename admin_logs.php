<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$db = Database::getInstance()->getConnection();
$logs = $db->query("SELECT l.*, a.username as admin_name 
                     FROM System_Logs l 
                     LEFT JOIN Admin_Users a ON l.admin_id = a.admin_id 
                     ORDER BY l.created_at DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Logs - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .btn-clear {
            background: rgba(255,68,68,0.15);
            color: #FF6B6B;
            padding: 8px 20px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-base);
        }
        .btn-clear:hover {
            background: #FF6B6B;
            color: white;
            transform: scale(1.05);
        }
        .log-entry {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            border-bottom: 1px solid var(--border-color);
            transition: all var(--transition-fast);
        }
        .log-entry:hover {
            background: rgba(255,107,0,0.04);
        }
        .log-entry .info .action {
            font-weight: 600;
            color: var(--primary-light);
        }
        .log-entry .info .description {
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        .log-entry .meta {
            text-align: right;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .log-entry .meta .admin {
            font-weight: 600;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <img src="assets/images/logo.png" alt="Logo" onerror="this.style.display='none'">
                <span>USTED-K Gym</span>
            </div>
            <nav class="sidebar-menu">
                <div class="sidebar-menu-label">Main</div>
                <a href="admin_dashboard.php" class="sidebar-item">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                <a href="admin_members.php" class="sidebar-item">
                    <i class="fas fa-users"></i> Members
                </a>
                <a href="admin_instructors.php" class="sidebar-item">
                    <i class="fas fa-chalkboard-teacher"></i> Instructors
                </a>
                <a href="admin_memberships.php" class="sidebar-item">
                    <i class="fas fa-id-card"></i> Memberships
                </a>
                <div class="sidebar-menu-label" style="margin-top: 20px;">Training</div>
                <a href="admin_training_types.php" class="sidebar-item">
                    <i class="fas fa-dumbbell"></i> Training Types
                </a>
                <a href="admin_training_sections.php" class="sidebar-item">
                    <i class="fas fa-calendar-alt"></i> Sections
                </a>
                <div class="sidebar-menu-label" style="margin-top: 20px;">Financial</div>
                <a href="admin_payments.php" class="sidebar-item">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
                <a href="admin_reports.php" class="sidebar-item">
                    <i class="fas fa-chart-line"></i> Reports
                </a>
                <div class="sidebar-menu-label" style="margin-top: 20px;">System</div>
                <a href="admin_logs.php" class="sidebar-item active">
                    <i class="fas fa-history"></i> System Logs
                </a>
                <a href="logout.php" class="sidebar-item" style="margin-top: 20px; color: #FF6B6B;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2>System Logs</h2>
                        <div class="breadcrumb">Admin / <span>Logs</span></div>
                    </div>
                </div>
                <div class="top-nav-right">
                    <button class="btn-clear" onclick="if(confirm('Clear all logs?')){alert('Logs cleared!');}">
                        <i class="fas fa-trash"></i> Clear Logs
                    </button>
                    <div class="profile-dropdown">
                        <img src="assets/images/default-avatar.png" alt="Admin">
                        <div class="info">
                            <div class="name"><?= htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin') ?></div>
                            <div class="role"><?= ucfirst(str_replace('_', ' ', $_SESSION['admin_role'] ?? 'admin')) ?></div>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="page-header animate-fade-in">
                <div>
                    <h2 style="font-size: 1.5rem; font-weight: 600;">Activity Log</h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Monitor all system activities and admin actions</p>
                </div>
                <span style="color: var(--text-muted); font-size: 0.875rem;">
                    <i class="fas fa-database"></i> <?= count($logs) ?> entries
                </span>
            </div>

            <div class="card animate-fade-in">
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($logs)): ?>
                        <p style="color: var(--text-muted); text-align: center; padding: 40px 0;">
                            <i class="fas fa-check-circle" style="font-size: 2rem; display: block; margin-bottom: 8px; color: #00B894;"></i>
                            No logs found
                        </p>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <div class="log-entry">
                                <div class="info">
                                    <div class="action">
                                        <i class="fas fa-<?= strpos(strtolower($log['action']), 'login') !== false ? 'sign-in-alt' : (strpos(strtolower($log['action']), 'delete') !== false ? 'trash' : 'edit') ?>"></i>
                                        <?= htmlspecialchars($log['action']) ?>
                                    </div>
                                    <div class="description"><?= htmlspecialchars($log['description']) ?></div>
                                </div>
                                <div class="meta">
                                    <div class="admin">
                                        <?= htmlspecialchars($log['admin_name'] ?? 'System') ?>
                                    </div>
                                    <div><?= timeAgo($log['created_at']) ?></div>
                                    <div style="font-size: 0.65rem; color: var(--text-muted);">
                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="footer">
                &copy; <?= date('Y') ?> USTED-K Gym Center • Security & Audit
            </div>
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