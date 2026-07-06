<?php
// export_members.php - download all members as a real CSV (opens in Excel)
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();
$rows = $db->query("SELECT m.member_id, m.first_name, m.last_name, m.email, m.telephone,
                           m.username, m.index_number, mt.name AS membership, m.status,
                           m.registration_date, m.membership_end_date
                    FROM Members m
                    LEFT JOIN Membership_Type mt ON m.membership_type_id = mt.membership_type_id
                    ORDER BY m.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$filename = 'members_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
// UTF-8 BOM so Excel shows accents/symbols correctly
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
fputcsv($out, ['Member ID', 'First Name', 'Last Name', 'Email', 'Telephone', 'Username', 'Index Number', 'Membership', 'Status', 'Registered', 'Membership Ends']);
foreach ($rows as $r) {
    fputcsv($out, $r);
}
fclose($out);
exit;
