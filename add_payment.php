<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$db = Database::getInstance()->getConnection();
$members = $db->query("SELECT member_id, first_name, last_name, email FROM Members WHERE status = 'approved'")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberId = intval($_POST['member_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $method = $_POST['payment_method'] ?? '';
    $status = $_POST['status'] ?? 'completed';
    $description = trim($_POST['description'] ?? '');
    
    if ($memberId <= 0 || $amount <= 0 || empty($method)) {
        $error = 'Please fill in all required fields.';
    } else {
        $reference = generateReferenceNumber();
        $sql = "INSERT INTO Payment (member_id, amount, payment_method, reference_number, status, description, payment_date) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        if ($stmt->execute([$memberId, $amount, $method, $reference, $status, $description])) {
            $success = 'Payment recorded successfully! Reference: ' . $reference;
        } else {
            $error = 'Failed to record payment.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Record Payment - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-container {
            max-width: 500px;
            margin: 0 auto;
        }
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
                        <h2>Record Payment</h2>
                        <div class="breadcrumb">Admin / Payments / <span>Record</span></div>
                    </div>
                </div>
            </nav>

            <div class="form-container animate-fade-in">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-hand-holding-usd" style="color: var(--primary-light);"></i> New Payment</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                                <br><a href="receipt.php?id=<?= $db->lastInsertId() ?>" style="color: var(--primary-light);">View Receipt</a>
                            </div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Member *</label>
                                <select name="member_id" class="form-control" required>
                                    <option value="">Select Member...</option>
                                    <?php foreach ($members as $m): ?>
                                        <option value="<?= $m['member_id'] ?>">
                                            <?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name'] . ' (' . $m['email'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Amount (₵) *</label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Payment Method *</label>
                                <select name="payment_method" class="form-control" required>
                                    <option value="">Select...</option>
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="mobile_money">Mobile Money</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="completed">Completed</option>
                                    <option value="pending">Pending</option>
                                    <option value="failed">Failed</option>
                                    <option value="refunded">Refunded</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="e.g., Monthly membership fee"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-save"></i> Record Payment
                            </button>
                        </form>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 16px;">
                    <a href="admin_payments.php" style="color: var(--text-muted);">
                        <i class="fas fa-arrow-left"></i> Back to Payments
                    </a>
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