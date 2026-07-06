<?php
// export_payments.php - download all payments as a real CSV (opens in Excel)
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();
$rows = $db->query("SELECT p.payment_id, p.reference_number,
                           CONCAT(m.first_name,' ',m.last_name) AS member,
                           p.amount, p.payment_method, p.status, p.payment_date, p.description
                    FROM Payment p
                    LEFT JOIN Members m ON p.member_id = m.member_id
                    ORDER BY p.payment_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$filename = 'payments_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
fputcsv($out, ['Payment ID', 'Reference', 'Member', 'Amount (GHS)', 'Method', 'Status', 'Date', 'Description']);
foreach ($rows as $r) {
    fputcsv($out, $r);
}
fclose($out);
exit;
