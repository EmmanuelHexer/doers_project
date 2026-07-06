<?php
// suspend_member.php - suspend a member
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $db = Database::getInstance()->getConnection();
    $db->prepare("UPDATE Members SET status = 'suspended' WHERE member_id = ?")->execute([$id]);
}

header('Location: admin_members.php');
exit;
