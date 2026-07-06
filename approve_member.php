<?php
// approve_member.php - approve (or reactivate) a member
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $db = Database::getInstance()->getConnection();
    // Set approved and (re)start the membership period based on the plan duration.
    $sql = "UPDATE Members m
            LEFT JOIN Membership_Type mt ON m.membership_type_id = mt.membership_type_id
            SET m.status = 'approved',
                m.membership_start_date = CURDATE(),
                m.membership_end_date = DATE_ADD(CURDATE(), INTERVAL COALESCE(mt.duration_months, 1) MONTH)
            WHERE m.member_id = ?";
    $db->prepare($sql)->execute([$id]);
}

header('Location: admin_members.php');
exit;
