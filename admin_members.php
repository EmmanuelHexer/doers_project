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

$sql = "SELECT m.*, mt.name as membership_name 
        FROM Members m 
        LEFT JOIN Membership_Type mt ON m.membership_type_id = mt.membership_type_id 
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ? OR m.username LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}
if ($status) {
    $sql .= " AND m.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY m.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Member Management - Admin</title>
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
        .btn-approve { background: rgba(0, 184, 148, 0.15); color: #00B894; }
        .btn-suspend { background: rgba(253, 203, 110, 0.15); color: #FDCB6E; }
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
                <a href="admin_members.php" class="sidebar-item active">
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
                        <h2>Member Management</h2>
                        <div class="breadcrumb">Admin / <span>Members</span></div>
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
                    <h2 style="font-size: 1.5rem; font-weight: 600;">All Members</h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Manage all registered members</p>
                </div>
                <a href="register.php" class="btn-add" target="_blank">
                    <i class="fas fa-user-plus"></i> Add Member
                </a>
            </div>

            <div class="filters animate-fade-in">
                <input type="text" placeholder="Search members..." id="searchInput" value="<?= htmlspecialchars($search) ?>"
                       onkeyup="if(event.key==='Enter') window.location.href='?search='+this.value">
                <select onchange="window.location.href='?status='+this.value">
                    <option value="">All Status</option>
                    <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $status == 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="suspended" <?= $status == 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    <option value="expired" <?= $status == 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>

            <div class="card animate-fade-in">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Contact</th>
                                <th>Membership</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                                <tr><td colspan="6" style="padding: 40px; text-align: center; color: var(--text-muted);">No members found</td></tr>
                            <?php else: ?>
                                <?php foreach ($members as $member): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <img src="assets/images/default-avatar.png" class="avatar-sm" alt="">
                                            <div>
                                                <div style="font-weight: 600;"><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted);">@<?= htmlspecialchars($member['username']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.875rem;"><?= htmlspecialchars($member['email']) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($member['telephone'] ?? 'N/A') ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($member['membership_name'] ?? 'N/A') ?></td>
                                    <td><span class="status-badge <?= $member['status'] ?>"><?= ucfirst($member['status']) ?></span></td>
                                    <td style="font-size: 0.8rem;"><?= date('M d, Y', strtotime($member['created_at'])) ?></td>
                                    <td style="text-align: right;">
                                        <div class="table-actions" style="display: flex; gap: 4px; justify-content: flex-end;">
                                            <a href="view_member.php?id=<?= $member['member_id'] ?>" class="btn-view"><i class="fas fa-eye"></i></a>
                                            <a href="edit_member.php?id=<?= $member['member_id'] ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                            <?php if ($member['status'] == 'pending'): ?>
                                                <a href="approve_member.php?id=<?= $member['member_id'] ?>" class="btn-approve"><i class="fas fa-check"></i></a>
                                            <?php endif; ?>
                                            <?php if ($member['status'] == 'approved'): ?>
                                                <a href="suspend_member.php?id=<?= $member['member_id'] ?>" class="btn-suspend"><i class="fas fa-ban"></i></a>
                                            <?php endif; ?>
                                            <?php if ($member['status'] == 'suspended'): ?>
                                                <a href="approve_member.php?id=<?= $member['member_id'] ?>" class="btn-approve"><i class="fas fa-undo"></i></a>
                                            <?php endif; ?>
                                            <a href="delete_member.php?id=<?= $member['member_id'] ?>" class="btn-delete" onclick="return confirm('Delete this member?')"><i class="fas fa-trash"></i></a>
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
                &copy; <?= date('Y') ?> USTED-K Gym Center • Member Management
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