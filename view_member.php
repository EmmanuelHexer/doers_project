<?php
// view_member.php - full member details
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();
$id = intval($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT m.*, mt.name AS membership_name, mt.fee,
                             CONCAT(i.first_name,' ',i.last_name) AS instructor_name
                      FROM Members m
                      LEFT JOIN Membership_Type mt ON m.membership_type_id = mt.membership_type_id
                      LEFT JOIN Instructor i ON m.assigned_instructor_id = i.instructor_id
                      WHERE m.member_id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    header('Location: admin_members.php');
    exit;
}

$pstmt = $db->prepare("SELECT amount, payment_method, status, payment_date, reference_number FROM Payment WHERE member_id = ? ORDER BY payment_date DESC LIMIT 10");
$pstmt->execute([$id]);
$payments = $pstmt->fetchAll();

$active = 'members';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Details - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        .detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .detail-item { padding: 12px 16px; background: var(--bg-input); border-radius: var(--radius-md); }
        .detail-item .k { font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-item .v { font-weight: 600; margin-top: 2px; }
        @media (max-width: 600px) { .detail-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body style="background: var(--bg-dark);">
    <div class="main-wrapper">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div><h2>Member Details</h2><div class="breadcrumb">Admin / Members / <span>View</span></div></div>
                </div>
            </nav>

            <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <h2 style="font-size:1.5rem;"><?= htmlspecialchars($member['first_name'].' '.$member['last_name']) ?>
                    <span class="status-badge <?= $member['status'] ?>" style="margin-left:8px;"><?= ucfirst($member['status']) ?></span>
                </h2>
                <div style="display:flex; gap:8px;">
                    <a href="edit_member.php?id=<?= $member['member_id'] ?>" class="btn btn-primary" style="padding:8px 16px;"><i class="fas fa-edit"></i> Edit</a>
                    <?php if ($member['status'] === 'pending'): ?>
                        <a href="approve_member.php?id=<?= $member['member_id'] ?>" class="btn" style="padding:8px 16px; background:rgba(0,184,148,0.15); color:#00B894;"><i class="fas fa-check"></i> Approve</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card animate-fade-in">
                <div class="card-header"><h5><i class="fas fa-user" style="color:var(--primary-light);"></i> Profile</h5></div>
                <div class="card-body">
                    <div class="detail-grid">
                        <div class="detail-item"><div class="k">Username</div><div class="v">@<?= htmlspecialchars($member['username']) ?></div></div>
                        <div class="detail-item"><div class="k">Email</div><div class="v"><?= htmlspecialchars($member['email']) ?></div></div>
                        <div class="detail-item"><div class="k">Telephone</div><div class="v"><?= htmlspecialchars($member['telephone'] ?: 'N/A') ?></div></div>
                        <div class="detail-item"><div class="k">Index Number</div><div class="v"><?= htmlspecialchars($member['index_number'] ?: 'N/A') ?></div></div>
                        <div class="detail-item"><div class="k">Membership</div><div class="v"><?= htmlspecialchars($member['membership_name'] ?: 'None') ?></div></div>
                        <div class="detail-item"><div class="k">Assigned Instructor</div><div class="v"><?= htmlspecialchars($member['instructor_name'] ?: 'None') ?></div></div>
                        <div class="detail-item"><div class="k">Start Date</div><div class="v"><?= $member['membership_start_date'] ? formatDate($member['membership_start_date']) : 'N/A' ?></div></div>
                        <div class="detail-item"><div class="k">End Date</div><div class="v"><?= $member['membership_end_date'] ? formatDate($member['membership_end_date']) : 'N/A' ?></div></div>
                        <div class="detail-item"><div class="k">Registered</div><div class="v"><?= formatDate($member['created_at']) ?></div></div>
                        <div class="detail-item"><div class="k">Health Status</div><div class="v"><?= htmlspecialchars($member['health_status'] ?: 'N/A') ?></div></div>
                    </div>
                </div>
            </div>

            <div class="card animate-fade-in" style="margin-top:20px;">
                <div class="card-header"><h5><i class="fas fa-credit-card" style="color:var(--primary-light);"></i> Recent Payments</h5></div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <p style="color:var(--text-muted);">No payments recorded.</p>
                    <?php else: ?>
                        <div class="table-wrapper">
                        <table class="table">
                            <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th><th>Reference</th></tr></thead>
                            <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td style="font-size:0.85rem;"><?= formatDate($p['payment_date']) ?></td>
                                    <td style="font-weight:600;"><?= formatCurrency($p['amount']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst(str_replace('_',' ',$p['payment_method']))) ?></td>
                                    <td><span class="status-badge <?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                                    <td style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($p['reference_number']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="text-align:center; margin-top:16px;">
                <a href="admin_members.php" style="color:var(--text-muted);"><i class="fas fa-arrow-left"></i> Back to Members</a>
            </div>
            <div class="footer">&copy; <?= date('Y') ?> USTED-K Gym Center</div>
        </main>
    </div>
    <script src="assets/js/script.js"></script>
    <script>document.getElementById('sidebarToggle').addEventListener('click',function(){document.getElementById('sidebar').classList.toggle('open');});</script>
</body>
</html>
