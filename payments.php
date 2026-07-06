<?php
session_start();

if (!isset($_SESSION['member_logged_in']) || $_SESSION['member_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$db = Database::getInstance()->getConnection();
$member_id = $_SESSION['member_id'];

$sql = "SELECT * FROM Payment WHERE member_id = ? ORDER BY payment_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$member_id]);
$payments = $stmt->fetchAll();

$totalPaid = $db->prepare("SELECT SUM(amount) as total FROM Payment WHERE member_id = ? AND status = 'completed'");
$totalPaid->execute([$member_id]);
$totalPaid = $totalPaid->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Payments - USTED-K Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
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
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php $active = 'payments'; include __DIR__ . '/includes/member_sidebar.php'; ?>

        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2>My Payments</h2>
                        <div class="breadcrumb">Home / <span>Payments</span></div>
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

            <div class="stats-row animate-fade-in">
                <div class="stat-box">
                    <div class="number"><?= formatCurrency($totalPaid) ?></div>
                    <div class="label">Total Paid</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= number_format(count($payments)) ?></div>
                    <div class="label">Transactions</div>
                </div>
            </div>

            <div class="page-header animate-fade-in" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
                <div>
                    <h2 style="font-size: 1.5rem; font-weight: 600;">Payment History</h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">View all your transactions</p>
                </div>
                <a href="book_session.php" class="btn-add">
                    <i class="fas fa-hand-holding-usd"></i> Make Payment
                </a>
            </div>

            <div class="card animate-fade-in">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th style="text-align: right;">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr><td colspan="6" style="padding: 40px; text-align: center; color: var(--text-muted);">No payments found</td></tr>
                            <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><code style="background: rgba(255,107,0,0.1); padding: 2px 10px; border-radius: 4px; font-size: 0.75rem;"><?= htmlspecialchars($payment['reference_number']) ?></code></td>
                                    <td><strong><?= formatCurrency($payment['amount']) ?></strong></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></td>
                                    <td><span class="status-badge <?= $payment['status'] ?>"><?= ucfirst($payment['status']) ?></span></td>
                                    <td style="font-size: 0.8rem;"><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                    <td style="text-align: right;">
                                        <a href="receipt.php?id=<?= $payment['payment_id'] ?>" class="btn btn-sm btn-outline">
                                            <i class="fas fa-file-invoice"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="footer">
                &copy; <?= date('Y') ?> USTED-K Gym Center
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