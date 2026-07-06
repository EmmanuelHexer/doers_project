<?php
// edit_payment.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();
$id = intval($_GET['id'] ?? $_POST['payment_id'] ?? 0);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberId = intval($_POST['member_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $method = $_POST['payment_method'] ?? '';
    $status = $_POST['status'] ?? 'completed';
    $desc = trim($_POST['description'] ?? '');

    if ($memberId <= 0 || $amount <= 0 || $method === '') {
        $error = 'Please fill in all required fields.';
    } else {
        $db->prepare("UPDATE Payment SET member_id=?, amount=?, payment_method=?, status=?, description=? WHERE payment_id=?")
           ->execute([$memberId, $amount, $method, $status, $desc, $id]);
        $success = 'Payment updated successfully.';
    }
}

$stmt = $db->prepare("SELECT * FROM Payment WHERE payment_id = ?");
$stmt->execute([$id]);
$payment = $stmt->fetch();
if (!$payment) { header('Location: admin_payments.php'); exit; }

$members = $db->query("SELECT member_id, first_name, last_name, email FROM Members ORDER BY first_name")->fetchAll();
$methods = ['cash'=>'Cash','card'=>'Card','bank_transfer'=>'Bank Transfer','mobile_money'=>'Mobile Money'];
$statuses = ['completed'=>'Completed','pending'=>'Pending','failed'=>'Failed','refunded'=>'Refunded'];
$active = 'payments';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Payment - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>.form-container { max-width: 520px; margin: 0 auto; }</style>
</head>
<body style="background: var(--bg-dark);">
    <div class="main-wrapper">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <main class="main-content" id="mainContent">
            <nav class="top-nav">
                <div class="top-nav-left">
                    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <div><h2>Edit Payment</h2><div class="breadcrumb">Admin / Payments / <span>Edit</span></div></div>
                </div>
            </nav>
            <div class="form-container animate-fade-in">
                <div class="card">
                    <div class="card-header"><h5><i class="fas fa-receipt" style="color:var(--primary-light);"></i> <?= htmlspecialchars($payment['reference_number']) ?></h5></div>
                    <div class="card-body">
                        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
                        <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
                        <form method="POST" action="edit_payment.php?id=<?= $payment['payment_id'] ?>">
                            <input type="hidden" name="payment_id" value="<?= $payment['payment_id'] ?>">
                            <div class="form-group"><label class="form-label">Member *</label>
                                <select name="member_id" class="form-control" required>
                                    <?php foreach ($members as $m): ?>
                                        <option value="<?= $m['member_id'] ?>" <?= $payment['member_id']==$m['member_id']?'selected':'' ?>><?= htmlspecialchars($m['first_name'].' '.$m['last_name'].' ('.$m['email'].')') ?></option>
                                    <?php endforeach; ?>
                                </select></div>
                            <div class="form-group"><label class="form-label">Amount (₵) *</label><input type="number" name="amount" class="form-control" step="0.01" min="0.01" value="<?= htmlspecialchars($payment['amount']) ?>" required></div>
                            <div class="form-group"><label class="form-label">Payment Method *</label>
                                <select name="payment_method" class="form-control" required>
                                    <?php foreach ($methods as $k=>$v): ?>
                                        <option value="<?= $k ?>" <?= $payment['payment_method']==$k?'selected':'' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select></div>
                            <div class="form-group"><label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <?php foreach ($statuses as $k=>$v): ?>
                                        <option value="<?= $k ?>" <?= $payment['status']==$k?'selected':'' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select></div>
                            <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($payment['description']) ?></textarea></div>
                            <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fas fa-save"></i> Save Changes</button>
                        </form>
                    </div>
                </div>
                <div style="text-align:center; margin-top:16px;"><a href="admin_payments.php" style="color:var(--text-muted);"><i class="fas fa-arrow-left"></i> Back to Payments</a></div>
            </div>
            <div class="footer">&copy; <?= date('Y') ?> USTED-K Gym Center</div>
        </main>
    </div>
    <script src="assets/js/script.js"></script>
    <script>document.getElementById('sidebarToggle').addEventListener('click',function(){document.getElementById('sidebar').classList.toggle('open');});</script>
</body>
</html>
