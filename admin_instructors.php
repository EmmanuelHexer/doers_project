<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$db = Database::getInstance()->getConnection();
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT i.*, 
        (SELECT COUNT(*) FROM Training_Section WHERE instructor_id = i.instructor_id AND status = 'active') as active_sections
        FROM Instructor i WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (i.first_name LIKE ? OR i.last_name LIKE ? OR i.email LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($status) {
    $sql .= " AND i.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY i.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$instructors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Instructor Management - Admin</title>
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
        .filters {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .filters input, .filters select {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--bg-input);
            color: var(--text-primary);
            font-size: 0.875rem;
        }
        .filters input:focus, .filters select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.1);
        }
        .table-actions a {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            text-decoration: none;
            display: inline-block;
            transition: all var(--transition-fast);
        }
        .table-actions a:hover { transform: scale(1.05); }
        .btn-edit { background: rgba(255, 107, 0, 0.15); color: var(--primary-light); }
        .btn-delete { background: rgba(255, 68, 68, 0.15); color: #FF6B6B; }
        .btn-view { background: rgba(0, 184, 148, 0.15); color: #00B894; }
        .btn-assign { background: rgba(253, 203, 110, 0.15); color: #FDCB6E; }
        .avatar-sm {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
            transition: transform var(--transition-base);
        }
        tr:hover .avatar-sm { transform: scale(1.1); }
        .btn-add {
            background: var(--primary-gradient);
            color: white;
            padding: 10px 24px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition-base);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-add:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: var(--shadow-lg);
            color: white;
        }
        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        .status-dot.active { background: #00B894; }
        .status-dot.inactive { background: #FF6B6B; }
        .status-dot.on_leave { background: #FDCB6E; }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar -->
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
                <a href="admin_instructors.php" class="sidebar-item active">
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
                <a href="logout.php" class="sidebar-item" style="margin-top: 20px; color: #FF6B6B;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main -->
        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2>Instructor Management</h2>
                        <div class="breadcrumb">Admin / <span>Instructors</span></div>
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

            <div class="page-header animate-fade-in">
                <div>
                    <h2 style="font-size: 1.5rem; font-weight: 600;">All Instructors</h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Manage gym instructors and their assignments</p>
                </div>
                <a href="add_instructor.php" class="btn-add">
                    <i class="fas fa-plus"></i> Add Instructor
                </a>
            </div>

            <div class="filters animate-fade-in">
                <input type="text" placeholder="Search instructors..." id="searchInput" value="<?= htmlspecialchars($search) ?>"
                       onkeyup="if(event.key==='Enter') window.location.href='?search='+this.value">
                <select onchange="window.location.href='?status='+this.value">
                    <option value="">All Status</option>
                    <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="on_leave" <?= $status == 'on_leave' ? 'selected' : '' ?>>On Leave</option>
                </select>
            </div>

            <div class="card animate-fade-in">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Instructor</th>
                                <th>Contact</th>
                                <th>Specialization</th>
                                <th>Status</th>
                                <th>Sections</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($instructors)): ?>
                                <tr><td colspan="6" style="padding: 40px; text-align: center; color: var(--text-muted);">No instructors found</td></tr>
                            <?php else: ?>
                                <?php foreach ($instructors as $instructor): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <img src="assets/images/default-avatar.png" class="avatar-sm" alt="">
                                            <div>
                                                <div style="font-weight: 600;"><?= htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']) ?></div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($instructor['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.875rem;"><?= htmlspecialchars($instructor['telephone'] ?? 'N/A') ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($instructor['specialization'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="status-dot <?= $instructor['status'] ?>"></span>
                                        <?= ucfirst(str_replace('_', ' ', $instructor['status'])) ?>
                                    </td>
                                    <td>
                                        <span style="background: rgba(255,107,0,0.1); padding: 2px 12px; border-radius: 9999px; font-weight: 600; color: var(--primary-light);">
                                            <?= $instructor['active_sections'] ?? 0 ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="table-actions" style="display: flex; gap: 4px; justify-content: flex-end;">
                                            <a href="view_instructor.php?id=<?= $instructor['instructor_id'] ?>" class="btn-view"><i class="fas fa-eye"></i></a>
                                            <a href="edit_instructor.php?id=<?= $instructor['instructor_id'] ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                            <a href="assign_training.php?id=<?= $instructor['instructor_id'] ?>" class="btn-assign"><i class="fas fa-tags"></i></a>
                                            <a href="delete_instructor.php?id=<?= $instructor['instructor_id'] ?>" class="btn-delete" onclick="return confirm('Delete this instructor?')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="footer">
                &copy; <?= date('Y') ?> USTED-K Gym Center • Instructor Management
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