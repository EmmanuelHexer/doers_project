<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$db = Database::getInstance()->getConnection();

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$sql = "SELECT p.*, CONCAT(m.first_name, ' ', m.last_name) as member_name, m.email as member_email
        FROM Payment p
        JOIN Members m ON p.member_id = m.member_id
        WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ? OR p.reference_number LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}
if ($status !== '') {
    $sql .= " AND p.status = ?";
    $params[] = $status;
}
$sql .= " ORDER BY p.payment_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$totalRevenue = $db->query("SELECT SUM(amount) as total FROM Payment WHERE status = 'completed'")->fetch()['total'] ?? 0;
$pendingPayments = $db->query("SELECT COUNT(*) as count FROM Payment WHERE status = 'pending'")->fetch()['count'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Management - Admin</title>
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
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stats-row .stat-box {
            background: var(--bg-card);
            padding: 16px 20px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            text-align: center;
            transition: all var(--transition-base);
        }
        .stats-row .stat-box:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }
        .stats-row .stat-box .number {
            font-size: 1.5rem;
            font-weight: 800;
            font-family: var(--font-heading);
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stats-row .stat-box .label { color: var(--text-muted); font-size: 0.75rem; }
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
        .table-actions a {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            text-decoration: none;
            display: inline-block;
            transition: all var(--transition-fast);
        }
        .table-actions a:hover { transform: scale(1.05); }
        .btn-edit { background: rgba(255,107,0,0.15); color: var(--primary-light); }
        .btn-delete { background: rgba(255,68,68,0.15); color: #FF6B6B; }
        .btn-view { background: rgba(0,184,148,0.15); color: #00B894; }
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
                <a href="admin_payments.php" class="sidebar-item active">
                    <i class="fas fa-credit-card"></i> Payments
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
                        <h2>Payment Management</h2>
                        <div class="breadcrumb">Admin / <span>Payments</span></div>
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

            <div class="stats-row animate-fade-in">
                <div class="stat-box">
                    <div class="number"><?= formatCurrency($totalRevenue) ?></div>
                    <div class="label">Total Revenue</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= number_format($pendingPayments) ?></div>
                    <div class="label">Pending Payments</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= number_format(count($payments)) ?></div>
                    <div class="label">Total Transactions</div>
                </div>
            </div>

            <div class="page-header animate-fade-in">
                <div>
                    <h2 style="font-size: 1.5rem; font-weight: 600;">All Payments</h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Track and manage all financial transactions</p>
                </div>
                <a href="add_payment.php" class="btn-add">
                    <i class="fas fa-plus"></i> Record Payment
                </a>
            </div>

            <form method="GET" class="filters animate-fade-in" style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
                <input type="text" name="search" placeholder="Search by member, email or reference..." value="<?= htmlspecialchars($search) ?>"
                       style="flex:1; min-width:220px; padding:8px 16px; border:1px solid var(--border-color); border-radius:var(--radius-md); background:var(--bg-input); color:var(--text-primary); font-size:0.875rem;">
                <select name="status" onchange="this.form.submit()"
                        style="padding:8px 16px; border:1px solid var(--border-color); border-radius:var(--radius-md); background:var(--bg-input); color:var(--text-primary); font-size:0.875rem;">
                    <option value="">All Status</option>
                    <?php foreach (['completed'=>'Completed','pending'=>'Pending','failed'=>'Failed','refunded'=>'Refunded'] as $k=>$v): ?>
                        <option value="<?= $k ?>" <?= $status===$k?'selected':'' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary" style="padding:8px 20px;"><i class="fas fa-search"></i> Search</button>
                <?php if ($search !== '' || $status !== ''): ?>
                    <a href="admin_payments.php" class="btn btn-outline" style="padding:8px 20px;">Clear</a>
                <?php endif; ?>
            </form>

            <div class="card animate-fade-in">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Member</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr><td colspan="7" style="padding: 40px; text-align: center; color: var(--text-muted);">No payments found</td></tr>
                            <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><code style="background: rgba(255,107,0,0.1); padding: 2px 10px; border-radius: 4px; font-size: 0.75rem;"><?= htmlspecialchars($payment['reference_number']) ?></code></td>
                                    <td>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($payment['member_name']) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($payment['member_email']) ?></div>
                                    </td>
                                    <td><strong><?= formatCurrency($payment['amount']) ?></strong></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></td>
                                    <td><span class="status-badge <?= $payment['status'] ?>"><?= ucfirst($payment['status']) ?></span></td>
                                    <td style="font-size: 0.8rem;"><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                    <td style="text-align: right;">
                                        <div class="table-actions" style="display: flex; gap: 4px; justify-content: flex-end;">
                                            <a href="receipt.php?id=<?= $payment['payment_id'] ?>" class="btn-view"><i class="fas fa-file-invoice"></i></a>
                                            <a href="edit_payment.php?id=<?= $payment['payment_id'] ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                            <a href="delete_payment.php?id=<?= $payment['payment_id'] ?>" class="btn-delete" onclick="return confirm('Delete this payment?')"><i class="fas fa-trash"></i></a>
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
                &copy; <?= date('Y') ?> USTED-K Gym Center • Payment Management
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