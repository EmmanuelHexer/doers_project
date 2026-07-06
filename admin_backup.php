<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$db = Database::getInstance()->getConnection();
$backupPath = __DIR__ . '/backups/';

// Create backup directory if not exists
if (!is_dir($backupPath)) {
    mkdir($backupPath, 0755, true);
}

$backupFiles = glob($backupPath . '*.sql');
$backupFiles = array_reverse($backupFiles);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Backup - Admin</title>
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
        .btn-backup {
            background: var(--primary-gradient);
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-base);
        }
        .btn-backup:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-lg);
        }
        .btn-restore {
            background: rgba(253,203,110,0.15);
            color: #FDCB6E;
            padding: 6px 16px;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-base);
        }
        .btn-restore:hover {
            background: #FDCB6E;
            color: var(--bg-dark);
            transform: scale(1.05);
        }
        .btn-delete-backup {
            background: rgba(255,68,68,0.15);
            color: #FF6B6B;
            padding: 6px 16px;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-base);
        }
        .btn-delete-backup:hover {
            background: #FF6B6B;
            color: white;
            transform: scale(1.05);
        }
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            transition: all var(--transition-fast);
        }
        .backup-item:hover {
            background: rgba(255,107,0,0.04);
        }
        .backup-item .info .name {
            font-weight: 600;
        }
        .backup-item .info .size {
            color: var(--text-muted);
            font-size: 0.75rem;
        }
        .backup-item .actions {
            display: flex;
            gap: 8px;
        }
        .backup-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .backup-stats .stat-box {
            background: var(--bg-card);
            padding: 16px 20px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            text-align: center;
            transition: all var(--transition-base);
        }
        .backup-stats .stat-box:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }
        .backup-stats .stat-box .number {
            font-size: 1.5rem;
            font-weight: 800;
            font-family: var(--font-heading);
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .backup-stats .stat-box .label { color: var(--text-muted); font-size: 0.75rem; }
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
                <div class="sidebar-menu-label" style="margin-top: 20px;">System</div>
                <a href="admin_backup.php" class="sidebar-item active">
                    <i class="fas fa-database"></i> Backup
                </a>
                <a href="admin_logs.php" class="sidebar-item">
                    <i class="fas fa-history"></i> Logs
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
                        <h2>Database Backup</h2>
                        <div class="breadcrumb">Admin / <span>Backup</span></div>
                    </div>
                </div>
                <div class="top-nav-right">
                    <div class="profile-dropdown">
                        <img src="assets/images/default-avatar.png" alt="Admin">
                        <div class="info">
                            <div class="name"><?= htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin') ?></div>
                            <div class="role"><?= ucfirst(str_replace('_', ' ', $_SESSION['admin_role'] ?? 'admin')) ?></div>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="backup-stats animate-fade-in">
                <div class="stat-box">
                    <div class="number"><?= count($backupFiles) ?></div>
                    <div class="label">Total Backups</div>
                </div>
                <div class="stat-box">
                    <div class="number">
                        <?php 
                        $totalSize = 0;
                        foreach ($backupFiles as $file) {
                            $totalSize += filesize($file);
                        }
                        echo number_format($totalSize / 1024, 1) . ' KB';
                        ?>
                    </div>
                    <div class="label">Total Size</div>
                </div>
            </div>

            <div class="page-header animate-fade-in">
                <div>
                    <h2 style="font-size: 1.5rem; font-weight: 600;">Backup Files</h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Manage database backups and restore points</p>
                </div>
                <form method="POST" action="backup_create.php" style="display: inline;">
                    <button type="submit" class="btn-backup">
                        <i class="fas fa-download"></i> Create Backup
                    </button>
                </form>
            </div>

            <div class="card animate-fade-in">
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($backupFiles)): ?>
                        <p style="color: var(--text-muted); text-align: center; padding: 40px 0;">
                            <i class="fas fa-database" style="font-size: 2rem; display: block; margin-bottom: 8px;"></i>
                            No backup files found
                        </p>
                    <?php else: ?>
                        <?php foreach ($backupFiles as $file): ?>
                            <div class="backup-item">
                                <div class="info">
                                    <div class="name">
                                        <i class="fas fa-file-archive"></i>
                                        <?= basename($file) ?>
                                    </div>
                                    <div class="size">
                                        <?= number_format(filesize($file) / 1024, 1) ?> KB •
                                        <?= date('M d, Y h:i A', filemtime($file)) ?>
                                    </div>
                                </div>
                                <div class="actions">
                                    <a href="backup_download.php?file=<?= urlencode(basename($file)) ?>" class="btn-restore">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <button class="btn-restore" onclick="if(confirm('Restore this backup? This will overwrite current data.')){alert('Restore initiated!');}">
                                        <i class="fas fa-undo"></i> Restore
                                    </button>
                                    <button class="btn-delete-backup" onclick="if(confirm('Delete this backup?')){alert('Backup deleted!');}">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card animate-fade-in" style="margin-top: 16px;">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle" style="color: var(--primary-light);"></i> Backup Information</h5>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <h6 style="color: var(--primary-light);">What's Included</h6>
                            <ul style="color: var(--text-muted); font-size: 0.875rem; list-style: none; padding: 0;">
                                <li><i class="fas fa-check-circle" style="color: #00B894;"></i> All member data</li>
                                <li><i class="fas fa-check-circle" style="color: #00B894;"></i> Instructor records</li>
                                <li><i class="fas fa-check-circle" style="color: #00B894;"></i> Training schedules</li>
                                <li><i class="fas fa-check-circle" style="color: #00B894;"></i> Payment history</li>
                                <li><i class="fas fa-check-circle" style="color: #00B894;"></i> System settings</li>
                            </ul>
                        </div>
                        <div>
                            <h6 style="color: var(--primary-light);">Best Practices</h6>
                            <ul style="color: var(--text-muted); font-size: 0.875rem; list-style: none; padding: 0;">
                                <li><i class="fas fa-clock" style="color: #FDCB6E;"></i> Backup daily</li>
                                <li><i class="fas fa-shield-alt" style="color: var(--primary-light);"></i> Store offsite copies</li>
                                <li><i class="fas fa-history" style="color: #74B9FF;"></i> Keep 7 days of backups</li>
                                <li><i class="fas fa-test" style="color: #FF6B6B;"></i> Test restores regularly</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer">
                &copy; <?= date('Y') ?> USTED-K Gym Center • Backup & Recovery
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