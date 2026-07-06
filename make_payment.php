<?php
// make_payment.php - member submits a payment (recorded as pending for admin confirmation)
session_start();
if (!isset($_SESSION['member_logged_in']) || $_SESSION['member_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();
$member_id = $_SESSION['member_id'];

// Member + plan (to suggest the fee)
$stmt = $db->prepare("SELECT m.*, mt.name AS membership_name, mt.fee
                      FROM Members m LEFT JOIN Membership_Type mt ON m.membership_type_id = mt.membership_type_id
                      WHERE m.member_id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();
if (!$member) { session_destroy(); header('Location: login.php'); exit; }

$error = '';
$success = '';
$reference = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $method = $_POST['payment_method'] ?? '';
    $desc   = trim($_POST['description'] ?? '');
    $validMethods = ['cash','card','bank_transfer','mobile_money'];

    if ($amount <= 0 || !in_array($method, $validMethods, true)) {
        $error = 'Please enter a valid amount and choose a payment method.';
    } else {
        $reference = generateReferenceNumber();
        // Recorded as 'pending' — an admin confirms it as completed once received.
        $ok = $db->prepare("INSERT INTO Payment (member_id, amount, payment_method, reference_number, status, description, payment_date)
                            VALUES (?, ?, ?, ?, 'pending', ?, NOW())")
                 ->execute([$member_id, $amount, $method, $reference, $desc]);
        if ($ok) {
            $success = 'Payment submitted! Your reference is ' . $reference . '. It will be confirmed by an administrator shortly.';
        } else {
            $error = 'Could not submit your payment. Please try again.';
        }
    }
}

$active = 'make_payment';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment - USTED-K Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>.form-container { max-width: 520px; margin: 0 auto; }</style>
</head>
<body style="background: var(--bg-dark);">
    <div class="main-wrapper">
        <?php include __DIR__ . '/includes/member_sidebar.php'; ?>
        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div><h2>Make Payment</h2><div class="breadcrumb">Home / <span>Make Payment</span></div></div>
                </div>
                <div class="top-nav-right">
                    <div class="profile-dropdown">
                        <div class="avatar-initial"><?= strtoupper(substr($_SESSION['member_username'] ?? 'U', 0, 1)) ?></div>
                        <div class="info"><div class="name"><?= htmlspecialchars($_SESSION['member_name']) ?></div><div class="role">Member</div></div>
                    </div>
                </div>
            </nav>

            <div class="form-container animate-fade-in">
                <div class="card">
                    <div class="card-header"><h5><i class="fas fa-hand-holding-usd" style="color: var(--primary-light);"></i> Submit a Payment</h5></div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
                            <div style="text-align:center; margin-top:12px;"><a href="payments.php" class="btn btn-primary"><i class="fas fa-receipt"></i> View Payment History</a></div>
                        <?php else: ?>
                            <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
                            <?php if ($member['membership_name']): ?>
                                <p style="color: var(--text-muted); margin-bottom:16px;">
                                    Your plan: <strong style="color:var(--text-primary);"><?= htmlspecialchars($member['membership_name']) ?></strong>
                                    <?php if ($member['fee']): ?> &mdash; Fee: <strong style="color:var(--primary-light);"><?= formatCurrency($member['fee']) ?></strong><?php endif; ?>
                                </p>
                            <?php endif; ?>
                            <form method="POST">
                                <div class="form-group">
                                    <label class="form-label">Amount (₵) *</label>
                                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" value="<?= htmlspecialchars($member['fee'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Payment Method *</label>
                                    <select name="payment_method" class="form-control" required>
                                        <option value="">Select...</option>
                                        <option value="mobile_money">Mobile Money (MoMo)</option>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Note (optional)</label>
                                    <textarea name="description" class="form-control" rows="2" placeholder="e.g., Monthly membership fee for July"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fas fa-paper-plane"></i> Submit Payment</button>
                            </form>
                            <p style="color: var(--text-muted); font-size: 0.75rem; margin-top:12px; text-align:center;">
                                <i class="fas fa-info-circle"></i> Payments are marked <strong>pending</strong> until confirmed by an administrator.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="text-align:center; margin-top:16px;">
                    <a href="dashboard.php" style="color: var(--text-muted);"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
            <div class="footer">&copy; <?= date('Y') ?> USTED-K Gym Center</div>
        </main>
    </div>
    <script src="assets/js/script.js"></script>
    <script>document.getElementById('sidebarToggle').addEventListener('click',function(){document.getElementById('sidebar').classList.toggle('open');});</script>
</body>
</html>
