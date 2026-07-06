<?php
// delete_member.php - permanently delete a member (and dependent rows)
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $db = Database::getInstance()->getConnection();
    try {
        $db->beginTransaction();
        // Remove dependent records first to satisfy foreign keys.
        $db->prepare("DELETE FROM Member_Section WHERE member_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM Payment WHERE member_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM Notifications WHERE member_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM Members WHERE member_id = ?")->execute([$id]);
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
    }
}

header('Location: admin_members.php');
exit;
