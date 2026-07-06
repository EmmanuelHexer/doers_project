<?php
session_start();

if (!isset($_SESSION['member_logged_in']) && !isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$db = Database::getInstance()->getConnection();
$paymentId = intval($_GET['id'] ?? 0);

$sql = "SELECT p.*, CONCAT(m.first_name, ' ', m.last_name) as member_name, m.email, m.telephone,
        m.membership_type_id, mt.name as membership_name
        FROM Payment p
        JOIN Members m ON p.member_id = m.member_id
        LEFT JOIN Membership_Type mt ON m.membership_type_id = mt.membership_type_id
        WHERE p.payment_id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    die('Payment not found.');
}

// Check permission
if (isset($_SESSION['member_logged_in']) && $payment['member_id'] != $_SESSION['member_id']) {
    die('Unauthorized access.');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt - USTED-K Gym</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        .receipt-wrapper {
            max-width: 600px;
            margin: 40px auto;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            padding: 40px;
            box-shadow: var(--shadow-lg);
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 24px;
            margin-bottom: 24px;
        }
        .receipt-header .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .receipt-header h2 { margin: 8px 0 4px; }
        .receipt-header p { color: var(--text-muted); font-size: 0.875rem; }
        .receipt-details { margin-bottom: 24px; }
        .receipt-details .row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .receipt-details .row:last-child { border-bottom: none; }
        .receipt-details .label { color: var(--text-muted); font-weight: 500; }
        .receipt-details .value { font-weight: 600; }
        .receipt-total {
            background: var(--primary-gradient);
            color: white;
            padding: 16px 20px;
            border-radius: var(--radius-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
        }
        .receipt-total .label { opacity: 0.9; }
        .receipt-total .amount { font-size: 1.5rem; font-weight: 800; }
        .btn-print {
            width: 100%;
            padding: 12px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-base);
            margin-top: 16px;
        }
        .btn-print:hover {
            transform: scale(1.02);
            box-shadow: var(--shadow-lg);
        }
        @media print {
            body { background: white; }
            .receipt-wrapper { box-shadow: none; border: 1px solid #ddd; }
            .btn-print, .no-print { display: none; }
        }
    </style>
</head>
<body style="background: var(--bg-dark);">
    <div class="receipt-wrapper animate-scale-in">
        <div class="receipt-header">
            <div class="logo"><i class="fas fa-dumbbell" style="-webkit-text-fill-color: #FF6B00;"></i> USTED-K Gym</div>
            <h2>Payment Receipt</h2>
            <p>Thank you for your payment</p>
        </div>

        <div class="receipt-details">
            <div class="row">
                <span class="label">Reference Number</span>
                <span class="value"><code><?= htmlspecialchars($payment['reference_number']) ?></code></span>
            </div>
            <div class="row">
                <span class="label">Member</span>
                <span class="value"><?= htmlspecialchars($payment['member_name']) ?></span>
            </div>
            <div class="row">
                <span class="label">Email</span>
                <span class="value"><?= htmlspecialchars($payment['email']) ?></span>
            </div>
            <div class="row">
                <span class="label">Membership</span>
                <span class="value"><?= htmlspecialchars($payment['membership_name'] ?? 'N/A') ?></span>
            </div>
            <div class="row">
                <span class="label">Payment Method</span>
                <span class="value"><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></span>
            </div>
            <div class="row">
                <span class="label">Date</span>
                <span class="value"><?= date('F d, Y h:i A', strtotime($payment['payment_date'])) ?></span>
            </div>
            <div class="row">
                <span class="label">Status</span>
                <span class="value"><span class="status-badge <?= $payment['status'] ?>"><?= ucfirst($payment['status']) ?></span></span>
            </div>
        </div>

        <div class="receipt-total">
            <span class="label">Total Amount</span>
            <span class="amount"><?= formatCurrency($payment['amount']) ?></span>
        </div>

        <?php if ($payment['description']): ?>
            <div style="margin-top: 16px; color: var(--text-muted); font-size: 0.875rem;">
                <strong>Description:</strong> <?= htmlspecialchars($payment['description']) ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 24px; text-align: center; color: var(--text-muted); font-size: 0.75rem;">
            <p>USTED-K Campus • +233 550 669 957</p>
            <p>This is a computer-generated receipt. No signature required.</p>
        </div>

        <button onclick="window.print()" class="btn-print no-print">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <a href="javascript:history.back()" class="btn btn-outline no-print" style="display: block; margin-top: 8px; text-align: center;">
            <i class="fas fa-arrow-left"></i> Go Back
        </a>
    </div>
</body>
</html>