<?php
// report_print.php - full formatted report, printable / save-as-PDF
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance()->getConnection();

$totalMembers   = $db->query("SELECT COUNT(*) c FROM Members")->fetch()['c'];
$activeMembers  = $db->query("SELECT COUNT(*) c FROM Members WHERE status='approved'")->fetch()['c'];
$pendingMembers = $db->query("SELECT COUNT(*) c FROM Members WHERE status='pending'")->fetch()['c'];
$totalInstr     = $db->query("SELECT COUNT(*) c FROM Instructor")->fetch()['c'];
$totalRevenue   = $db->query("SELECT COALESCE(SUM(amount),0) t FROM Payment WHERE status='completed'")->fetch()['t'];
$monthRevenue   = $db->query("SELECT COALESCE(SUM(amount),0) t FROM Payment WHERE status='completed' AND MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetch()['t'];

$byMonth = $db->query("SELECT DATE_FORMAT(payment_date,'%Y-%m') ym, COUNT(*) n, SUM(amount) total
                       FROM Payment WHERE status='completed'
                       GROUP BY ym ORDER BY ym DESC LIMIT 6")->fetchAll();

$membershipDist = $db->query("SELECT mt.name, COUNT(m.member_id) count, COALESCE(mt.fee,0) fee
                              FROM Membership_Type mt
                              LEFT JOIN Members m ON mt.membership_type_id = m.membership_type_id
                              GROUP BY mt.membership_type_id ORDER BY count DESC")->fetchAll();

$recentPayments = $db->query("SELECT p.reference_number, CONCAT(m.first_name,' ',m.last_name) member,
                                     p.amount, p.payment_method, p.status, p.payment_date
                              FROM Payment p LEFT JOIN Members m ON p.member_id=m.member_id
                              ORDER BY p.payment_date DESC LIMIT 15")->fetchAll();

$expiringSoon = $db->query("SELECT CONCAT(first_name,' ',last_name) member, email, membership_end_date
                            FROM Members
                            WHERE status='approved' AND membership_end_date IS NOT NULL
                              AND membership_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                            ORDER BY membership_end_date ASC")->fetchAll();

$admin = $_SESSION['admin_fullname'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gym Management Report - <?= date('Y-m-d') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1a1a1a; background: #f4f4f4; padding: 24px; }
        .sheet { max-width: 900px; margin: 0 auto; background: #fff; padding: 40px; box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
        .rpt-header { border-bottom: 3px solid #FF6B00; padding-bottom: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; }
        .rpt-header h1 { font-size: 1.6rem; color: #FF6B00; }
        .rpt-header .sub { color: #666; font-size: 0.85rem; margin-top: 4px; }
        .rpt-header .meta { text-align: right; font-size: 0.8rem; color: #666; }
        h2 { font-size: 1.05rem; margin: 24px 0 10px; padding-bottom: 6px; border-bottom: 1px solid #ddd; color: #333; }
        .kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .kpi { border: 1px solid #e0e0e0; border-radius: 8px; padding: 14px; text-align: center; }
        .kpi .n { font-size: 1.5rem; font-weight: 800; color: #FF6B00; }
        .kpi .l { font-size: 0.75rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th { background: #FF6B00; color: #fff; text-align: left; padding: 8px 10px; }
        td { padding: 7px 10px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) td { background: #fafafa; }
        .right { text-align: right; }
        .toolbar { max-width: 900px; margin: 0 auto 16px; text-align: right; }
        .btn { background: #FF6B00; color: #fff; border: none; padding: 10px 22px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; font-size: 0.9rem; }
        .btn.grey { background: #666; }
        .rpt-footer { margin-top: 30px; padding-top: 14px; border-top: 1px solid #ddd; font-size: 0.75rem; color: #999; text-align: center; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; max-width: 100%; padding: 0; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="admin_reports.php" class="btn grey">&larr; Back</a>
        <button class="btn" onclick="window.print()">🖨 Print / Save as PDF</button>
    </div>
    <div class="sheet">
        <div class="rpt-header">
            <div>
                <h1>USTED-K Gym Center</h1>
                <div class="sub">Management Report &amp; Analytics</div>
            </div>
            <div class="meta">
                Generated: <?= date('M d, Y g:i A') ?><br>
                By: <?= htmlspecialchars($admin) ?>
            </div>
        </div>

        <h2>Summary Overview</h2>
        <div class="kpis">
            <div class="kpi"><div class="n"><?= number_format($totalMembers) ?></div><div class="l">Total Members</div></div>
            <div class="kpi"><div class="n"><?= number_format($activeMembers) ?></div><div class="l">Active Members</div></div>
            <div class="kpi"><div class="n"><?= number_format($pendingMembers) ?></div><div class="l">Pending Approvals</div></div>
            <div class="kpi"><div class="n"><?= number_format($totalInstr) ?></div><div class="l">Instructors</div></div>
            <div class="kpi"><div class="n"><?= formatCurrency($totalRevenue) ?></div><div class="l">Total Revenue</div></div>
            <div class="kpi"><div class="n"><?= formatCurrency($monthRevenue) ?></div><div class="l">This Month</div></div>
        </div>

        <h2>Membership Distribution</h2>
        <table>
            <thead><tr><th>Membership Type</th><th class="right">Members</th><th class="right">Fee</th></tr></thead>
            <tbody>
            <?php if (empty($membershipDist)): ?><tr><td colspan="3">No membership types defined.</td></tr>
            <?php else: foreach ($membershipDist as $d): ?>
                <tr><td><?= htmlspecialchars($d['name']) ?></td><td class="right"><?= number_format($d['count']) ?></td><td class="right"><?= formatCurrency($d['fee']) ?></td></tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>

        <h2>Revenue by Month (last 6 months)</h2>
        <table>
            <thead><tr><th>Month</th><th class="right">Payments</th><th class="right">Revenue</th></tr></thead>
            <tbody>
            <?php if (empty($byMonth)): ?><tr><td colspan="3">No completed payments yet.</td></tr>
            <?php else: foreach ($byMonth as $m): ?>
                <tr><td><?= date('F Y', strtotime($m['ym'].'-01')) ?></td><td class="right"><?= number_format($m['n']) ?></td><td class="right"><?= formatCurrency($m['total']) ?></td></tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>

        <h2>Memberships Expiring Within 30 Days</h2>
        <table>
            <thead><tr><th>Member</th><th>Email</th><th class="right">Expires</th></tr></thead>
            <tbody>
            <?php if (empty($expiringSoon)): ?><tr><td colspan="3">No memberships expiring soon.</td></tr>
            <?php else: foreach ($expiringSoon as $e): ?>
                <tr><td><?= htmlspecialchars($e['member']) ?></td><td><?= htmlspecialchars($e['email']) ?></td><td class="right"><?= formatDate($e['membership_end_date']) ?></td></tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>

        <h2>Recent Payments</h2>
        <table>
            <thead><tr><th>Reference</th><th>Member</th><th>Method</th><th>Status</th><th class="right">Amount</th><th class="right">Date</th></tr></thead>
            <tbody>
            <?php if (empty($recentPayments)): ?><tr><td colspan="6">No payments recorded.</td></tr>
            <?php else: foreach ($recentPayments as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['reference_number']) ?></td>
                    <td><?= htmlspecialchars($p['member'] ?: 'N/A') ?></td>
                    <td><?= htmlspecialchars(ucfirst(str_replace('_',' ',$p['payment_method']))) ?></td>
                    <td><?= ucfirst($p['status']) ?></td>
                    <td class="right"><?= formatCurrency($p['amount']) ?></td>
                    <td class="right"><?= formatDate($p['payment_date']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>

        <div class="rpt-footer">
            USTED-K Gym Center &bull; Confidential Management Report &bull; &copy; <?= date('Y') ?>
        </div>
    </div>
    <script>
        // If opened with ?auto=1, trigger the print dialog immediately.
        if (new URLSearchParams(location.search).get('auto') === '1') { window.onload = () => window.print(); }
    </script>
</body>
</html>
